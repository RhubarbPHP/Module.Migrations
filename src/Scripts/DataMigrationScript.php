<?php


namespace Rhubarb\Scaffolds\Migrations\Scripts;

use PHPUnit\Runner\Exception;
use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Crown\Logging\Log;
use Rhubarb\Scaffolds\Migrations\MigrationsSettings;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\Not;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlEnumColumn;
use Rhubarb\Stem\Schema\Columns\Column;
use Rhubarb\Stem\Schema\ModelSchema;

/**
 * Class DataMigrationScript
 *
 * @package Rhubarb\Scaffolds\Migrations\Scripts
 */
abstract class DataMigrationScript implements MigrationScript
{
    protected $repoSchemas;

    /**
     * The splitFunctions takes a single variable: the contents of an $existingColumn. It must return an array
     * with that data split into the new columns. The array should return data in the exact same order as the columns
     * provided in $newColumns as that order is used to assign the new values.
     *
     * Note: The new Columns will also need added to the Model's class!
     *
     * @param Model    $model
     * @param string   $existingColumn
     * @param Column[] $newColumns
     * @param callable $splitFunction
     * @throws ImplementationException
     */
    protected final function splitColumn(
        string $modelClass,
        string $existingColumn,
        array $newColumns,
        callable $splitFunction
    ) {
        $type = 'split column';

        $model = $this->getModelFromClass($modelClass, $type);

        $this->addColumnsToSchema($model, $newColumns);
        $this->updateRepo($model, $this->getRepoSchema($model));

        foreach ($model::find(new Not(new Equals($existingColumn, ''))) as $row) {
            $data = $splitFunction($row->$existingColumn);
            if (count($data) != count($newColumns)) {
                $this->throwError('sort function response',
                    count($data) . " data returned for " . count($newColumns) . ' columns', $type);
            }
            foreach ($newColumns as $newColumn) {
                $columnName = $newColumn->columnName;
                $row->$columnName = array_shift($data);
            }
            $row->save();
        }
    }

    /**
     * @param string $model
     * @param string $columnName
     * @param string $currentValue
     * @param string $newValue
     * @throws ImplementationException
     * @throws \Rhubarb\Stem\Exceptions\FilterNotSupportedException
     */
    protected final function updateEnumOption(
        string $modelClass,
        string $columnName,
        string $currentValue,
        string $newValue
    ) {
        $type = 'update enum option';

        /** @var Model $model */
        $model = $this->getModelFromClass($modelClass, $type);
        /** @var ModelSchema $modelSchema */
        $modelSchema = $this->getRepoSchema($model);

        if (array_key_exists($columnName, $modelSchema->getColumns())) {
            /** @var MySqlEnumColumn $column */
            $column = $modelSchema->getColumns()[$columnName];
        } else {
            $this->throwError('column name', $columnName, $type);
        }
        // TODO: There is no base Enum column. This should be replaced with a generic enum class since we do not know it will be used solely on mysql.
        if (!is_a($column, MySqlEnumColumn::class)) {
            $this->throwError('column type', get_class($column), $type);
        }
        if (!in_array($currentValue, $column->enumValues)) {
            $this->throwError('current value', $currentValue, $type);
        }

        Log::info("Updating $columnName in $modelClass to replace $currentValue with $newValue");
        $column->enumValues = array_merge($column->enumValues, [$newValue]);
        if ($column->getDefaultValue() == $currentValue) {
            $column->defaultValue = $newValue;
        }
        $this->updateRepo($model, $modelSchema);
        self::replaceValueInColumn($model, $columnName, $currentValue, $newValue);
        $column->enumValues = array_diff($column->enumValues, [$currentValue]);
        $this->updateRepo($model, $modelSchema);
    }

    /**
     * @param Model  $model
     * @param string $columnName
     * @param string $currentValue
     * @param string $newValue
     * @throws \Rhubarb\Stem\Exceptions\FilterNotSupportedException
     */
    protected function replaceValueInColumn(Model $model, string $columnName, $currentValue, $newValue)
    {
        $collection = new RepositoryCollection(get_class($model));
        $collection->filter([new Equals($columnName, $currentValue)]);
        $count = $collection->count();
        $collection->enableRanging();
        $collection->setRange($startIndex = 0, 100);
        $collection->markRangeApplied();
        while ($startIndex < $count) {
            foreach ($collection as $row) {
                $row->$columnName = $newValue;
                $row->save();
            }
            $collection->setRange($startIndex += 100, 100);
        }
    }

    /**
     * @param Model $model
     * @return ModelSchema
     */
    protected function getRepoSchema(Model $model)
    {
        if ($this->repoSchemas[$model->getModelName()]) {
            return $this->repoSchemas[$model->getModelName()];
        }
        return ($this->repoSchemas[$model->getModelName()] = $model->getRepository()->getRepositorySchema());
    }

    /**
     * @param ModelSchema $modelSchema
     * @param array       $columns
     */
    protected function addColumnsToSchema(Model $model, array $columns)
    {
        foreach ($columns as $newColumn) {
            $this->getRepoSchema($model)->addColumn($newColumn);
            ($modelSchema ?? $modelSchema = $model->getSchema())->addColumn($newColumn);
        }
    }

    /**
     * @param Model            $model
     * @param ModelSchema|null $modelSchema
     */
    protected function updateRepo(Model $model, ModelSchema $modelSchema = null)
    {
        ($modelSchema ?? $model->getRepository()->getRepositorySchema())->checkSchema($model->getRepository());
    }

    /**
     * @param string $nameOfInvalidParam
     * @param string $invalidParam
     * @param string $typeOfDataMigration
     * @throws ImplementationException
     */
    protected function throwError($nameOfInvalidParam, $invalidParam, $typeOfDataMigration)
    {
        $msg = "Invalid $nameOfInvalidParam provided in $typeOfDataMigration operation: $invalidParam";
        Log::error($msg);
        throw new ImplementationException($msg);
    }

    /**
     * @param string      $modelClass
     * @param string|null $type
     * @throws ImplementationException
     */
    protected function getModelFromClass(string $modelClass, string $type = null)
    {
        $error = function () use ($modelClass, $type) {
            $this->throwError('model class', $modelClass, $type ?? '');
        };

        if (!class_exists($modelClass)) {
            $error();
        }

        try {
            /** @var Model $model */
            $model = new $modelClass();
        } catch (Exception $exception) {
            $error();
            throw new $exception;
        }

        if (get_class($model->getRepository()) !== MigrationsSettings::singleton()->repositoryType) {
            $error();
        }

        return $model;
    }
}