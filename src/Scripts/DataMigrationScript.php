<?php


namespace Rhubarb\Scaffolds\Migrations\Scripts;

use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Crown\Logging\Log;
use Rhubarb\Scaffolds\Migrations\MigrationsSettings;
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
        if (!class_exists($modelClass)) {
            $this->throwError('model', $modelClass, $type);
        }
        /** @var Model $model */
        $model = new $modelClass();

        $modelSchema = $model->getSchema();
        foreach ($newColumns as $newColumn) {
            if ($modelSchema->getColumns()[$newColumn->columnName] == null) {
                $modelSchema->addColumn($newColumn);
            }
        }
        $this->updateRepo($model, $modelSchema);

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

    protected function updateRepo(Model $model, ModelSchema $modelSchema = null)
    {
        ($modelSchema ?? $model->getRepository()->getRepositorySchema())->checkSchema($model->getRepository());
    }

    /**
     * @param Model  $model
     * @param string $columnName
     * @param string $currentValue
     * @param string $newValue
     * @throws ImplementationException
     */
    protected final function updateEnumOption(
        string $modelClass,
        string $columnName,
        string $currentValue,
        string $newValue
    ) {
        $type = 'update enum option';
        $throwErrorForModelClass = function () use ($modelClass, $type) {
            $this->throwError('model class', $modelClass, $type);
        };

        if (!class_exists($modelClass)) {
            $throwErrorForModelClass();
        }

        /** @var Model $model */
        $model = new $modelClass();
        /** @var ModelSchema $modelSchema */
        $modelSchema = $model->getRepository()->getRepositorySchema();

        if (get_class($model->getRepository()) !== MigrationsSettings::singleton()->repositoryType) {
            $throwErrorForModelClass();
        }

        if (array_key_exists($columnName, $modelSchema->getColumns())) {
            /** @var MySqlEnumColumn $column */
            $column = $modelSchema->getColumns()[$columnName];
        } else {
            $this->throwError('column name', $columnName, $type);
        }
        if (!is_a($column, MySqlEnumColumn::class)) {
            $this->throwError('column type', get_class($column), $type);
        }
        if (!in_array($currentValue, $column->enumValues)) {
            $this->throwError('current value', $currentValue, $type);
        }

        Log::info("Updating $columnName in $modelClass to replace $currentValue with $newValue");
        $column->enumValues = array_merge($column->enumValues, [$newValue]);
        $modelSchema->checkSchema($model->getRepository());
        self::replaceValueInColumn($model, $columnName, $currentValue, $newValue);
        $column->enumValues = array_diff($column->enumValues, [$currentValue]);
        $modelSchema->checkSchema($model->getRepository());
    }

    /**
     * @param Model  $model
     * @param string $columnName
     * @param string $currentValue
     * @param string $newValue
     * @param int    $page
     */
    protected function replaceValueInColumn(Model $model, string $columnName, $currentValue, $newValue, $page = 500)
    {
        $collection = $model::find(new Equals($columnName, $currentValue));
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
}