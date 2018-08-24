<?php


namespace Rhubarb\Scaffolds\Migrations\Scripts;

use Error;
use PHPUnit\Runner\Exception;
use Rhubarb\Crown\Logging\Log;
use Rhubarb\Scaffolds\Migrations\MigrationsSettings;
use Rhubarb\Stem\Collections\Collection;
use Rhubarb\Stem\Collections\RepositoryCollection;
use Rhubarb\Stem\Exceptions\FilterNotSupportedException;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Filters\Not;
use Rhubarb\Stem\Filters\OrGroup;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlEnumColumn;
use Rhubarb\Stem\Repositories\Offline\Offline;
use Rhubarb\Stem\Schema\Columns\Column;
use Rhubarb\Stem\Schema\ModelSchema;

/**
 * Class DataMigrationScript
 *
 * @package Rhubarb\Scaffolds\Migrations\Scripts
 */
abstract class DataMigrationScript implements MigrationScriptInterface
{
    /** @var string included in error messages when a migration fails for debugging purposes. */
    protected $currentMigrationType = null;

    protected function setCurrentMigrationType(string $type)
    {
        $this->currentMigrationType = $type;
    }

    /**
     * The splitFunctions takes a single variable: the contents of an $existingColumn. It must return an array
     * with that data split into the new columns. The array should return data in the exact same order as the columns
     * provided in $newColumns as that order is used to assign the new values.
     *
     * Note: The new Columns will also need added to the Model's class or they will be lost when the schema is updated
     * next!
     *
     * @param Model    $model
     * @param string   $existingColumn
     * @param Column[] $newColumns
     * @param callable $splitFunction
     */
    protected function splitColumn(
        string $modelClass,
        string $existingColumn,
        array $newColumns,
        callable $splitFunction
    ) {
        $this->setCurrentMigrationType('split column');

        $model = $this->getModelFromClass($modelClass);

        $this->addColumnsToSchema($model, $newColumns);
        $this->updateRepo($model, $this->getRepoSchema($model));

        foreach ($model::find(new Not(new Equals($existingColumn, ''))) as $row) {
            $data = $splitFunction($row->$existingColumn);
            if (count($data) != count($newColumns)) {
                $this->error('sort function response',
                    count($data) . " data returned for " . count($newColumns) . ' columns');
            }
            foreach ($newColumns as $newColumn) {
                $row->{$newColumn->columnName} = array_shift($data);
            }
            $row->save();
        }
    }

    /**
     * @param string $model
     * @param string $columnName
     * @param string $currentValue
     * @param string $newValue
     */
    protected function updateEnumOption(
        string $modelClass,
        string $columnName,
        string $currentValue,
        string $newValue
    ) {
        $this->setCurrentMigrationType('update enum option');

        /** @var Model $model */
        $model = $this->getModelFromClass($modelClass);
        /** @var ModelSchema $modelSchema */
        $modelSchema = $this->getRepoSchema($model);

        if (array_key_exists($columnName, $modelSchema->getColumns())) {
            /** @var MySqlEnumColumn $column */
            $column = $modelSchema->getColumns()[$columnName];
        } else {
            $this->error('column name', $columnName);
        }
        // TODO: There is no base Enum column. This should be replaced with a generic enum class since we do not know it will be used solely on mysql.
        if (!is_a($column, MySqlEnumColumn::class)) {
            $this->error('column type', get_class($column));
        }
        if (!in_array($currentValue, $column->enumValues)) {
            $this->error('current value', $currentValue);
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
     * @param string   $modelClass
     * @param string[] $existingColumnNames
     * @param Column   $newColumn
     * @param callable $mergeFunction
     */
    protected function mergeColumns(
        string $modelClass,
        array $existingColumnNames,
        Column $newColumn,
        callable $mergeFunction
    ) {
        $this->setCurrentMigrationType('merge columns');
        $model = $this->getModelFromClass($modelClass);
        $this->confirmColumnsInSchema($existingColumnNames, $model->getSchema());
        $this->addColumnsToSchema($model, [$newColumn]);
        $this->updateRepo($model);

        $newColumnName = $newColumn->columnName;
        // Only do rows that have some value in at least one of the existing columns
        foreach ($existingColumnNames as $existingColumnName) {
            $filters[] = new Not(new Equals($existingColumnName, ''));
        }
        $this->pagedUpdate(
            $model::find(new OrGroup($filters)),
            function (Model $row) use ($existingColumnNames, $newColumnName, $mergeFunction) {
                foreach ($existingColumnNames as $columnName) {
                    $existingData[] = $row->$columnName;
                }
                $row->{$newColumnName} = $mergeFunction(...$existingData);
                $row->save();
            });
    }

    /**
     * @param string      $columnName
     * @param ModelSchema $schema
     */
    protected function confirmColumnInSchema(string $columnName, ModelSchema $schema)
    {
        if (array_key_exists($columnName, $schema->getColumns()) === false) {
            $this->error('column name', $columnName);
        }
    }

    /**
     * @param string[]    $columnNames
     * @param ModelSchema $modelSchema
     */
    protected function confirmColumnsInSchema(array $columnNames, ModelSchema $modelSchema)
    {
        foreach ($columnNames as $columnName) {
            $this->confirmColumnInSchema($columnName, $modelSchema);
        }
    }

    /**
     * @param Column[] $columns
     * @return string[]
     */
    protected function getColumnNamesForColumns(array $columns): array
    {
        return array_map(function ($column) {
            return $column->columnName;
        }, $columns);
    }

    /**
     * @param string $modelClass
     * @param string $currentColumnName
     * @param string $newColumnName
     */
    protected function renameColumn(string $modelClass, $currentColumnName, $newColumnName)
    {
        $this->setCurrentMigrationType('rename column');
        $model = $this->getModelFromClass($modelClass);
        $this->confirmColumnInSchema($currentColumnName, $model->getSchema());
        $newColumn = clone $model->getSchema()->getColumns()[$currentColumnName];
        $newColumn->columnName = $newColumnName;
        $this->addColumnsToSchema($model, [$newColumn]);
        $this->updateRepo($model);
        $this->pagedUpdate(
            $model::find(new Not(new Equals($currentColumnName, ''))),
            function (Model $row) use ($currentColumnName, $newColumnName) {
                $row->$newColumnName = $row->$currentColumnName;
                $row->save();
            }
        );
    }

    /**
     * @param Collection $collection
     * @param callable   $loopedFunction
     */
    protected function pagedUpdate(Collection $collection, callable $loopedFunction)
    {
        $pageSize = MigrationsSettings::singleton()->pageSize;
        $count = $collection->count();

        $collection->enableRanging();
        $collection->setRange($startIndex = 0, $pageSize);
        $collection->markRangeApplied();
        while ($startIndex < $count) {
            foreach ($collection as $row) {
                $loopedFunction($row);
            }
            if (get_class($repo = $collection->getRepository()) !== Offline::class) {
                $repo->clearObjectCache();
            }
            $collection->setRange($startIndex += $pageSize, $pageSize);
        }
    }

    /**
     * @param Model  $model
     * @param string $columnName
     * @param string $currentValue
     * @param string $newValue
     */
    protected function replaceValueInColumn(Model $model, string $columnName, $currentValue, $newValue)
    {
        $collection = new RepositoryCollection(get_class($model));
        try {
            $collection->filter([new Equals($columnName, $currentValue)]);
        } catch (FilterNotSupportedException $e) {
            $this->error(
                "filter",
                "could not filter model " . get_class($model) . " for value $currentValue"
            );
        }

        $this->pagedUpdate($collection, function (Model $row) use ($columnName, $newValue) {
            $row->$columnName = $newValue;
            $row->save();
        });
    }

    /**
     * @param Model $model
     * @return ModelSchema
     */
    protected final function getRepoSchema(Model $model)
    {
        return $model->getRepository()->getRepositorySchema();
    }

    /**
     * @param ModelSchema $modelSchema
     * @param Column[]    $columns
     */
    protected final function addColumnsToSchema(Model $model, array $columns)
    {
        foreach ($columns as $newColumn) {
            $this->getRepoSchema($model)->addColumn($newColumn);
            ($modelSchema ?? $modelSchema = $model->getSchema())->addColumn($newColumn);
        }
    }

    /**
     * @param Model            $model
     * @param ModelSchema|null $repoSchema
     */
    protected final function updateRepo(Model $model, ModelSchema $repoSchema = null)
    {
        ($repoSchema ?? $model->getRepository()->getRepositorySchema())->checkSchema($model->getRepository());
    }

    /**
     * MUST throw an Error to stop MigrationScripts being executed.
     *
     * @param string $nameOfInvalidParam
     * @param string $invalidParam
     * @throws Error
     */
    protected function error(string $nameOfInvalidParam, string $invalidParam)
    {
        $msg =
            "Data Migration Error: Invalid $nameOfInvalidParam provided in {$this->currentMigrationType} operation"
            . ($invalidParam !== null ? " of value $invalidParam" : '');
        Log::error($msg);
        throw new Error($msg);
    }

    /**
     * @param string $modelClass
     * @return Model
     */
    protected function getModelFromClass(string $modelClass): Model
    {
        $error = function () use ($modelClass) {
            $this->error('model class', $modelClass);
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