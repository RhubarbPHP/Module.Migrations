<?php


namespace Rhubarb\Scaffolds\Migrations\Tests\Fixtures;


use Rhubarb\Scaffolds\Migrations\Scripts\DataMigrationScript;
use Rhubarb\Stem\Schema\Columns\Column;

class TestDataMigrationScript extends DataMigrationScript
{

    /**
     * Primary logic of the script should be implemented or called here.
     *
     */
    public function execute()
    {
        return null;
    }

    /**
     * @param string $modelClass
     * @param string $columnName
     * @param string $currentValue
     * @param string $newValue
     */
    public function performEnumUpdate(
        string $modelClass,
        string $columnName,
        string $currentValue,
        string $newValue
    ) {
        $this->updateEnumOption($modelClass, $columnName, $currentValue, $newValue);
    }

    public function performMergeColumns(
        string $modelClass,
        array $existingColumns,
        Column $newColumn,
        callable $mergeFunction
    ) {
        $this->mergeColumns(...func_get_args());
    }


    /**
     * @param string   $modelClass
     * @param string   $existingColumnName
     * @param array    $newColumns
     * @param callable $sortFunction
     */
    public function performSplitColumn(
        string $modelClass,
        string $existingColumnName,
        array $newColumns,
        callable $sortFunction
    ) {
        $this->splitColumn($modelClass, $existingColumnName, $newColumns, $sortFunction);
    }

    /**
     * @param string $modelClass
     * @param string $currentColumnName
     * @param string $newColumnName
     */
    public function performRenameColumn($modelClass, $currentColumnName, $newColumnName) {
        $this->renameColumn($modelClass, $currentColumnName, $newColumnName);
    }

    /**
     * The application version this script should be ran on
     *
     * @return int
     */
    public function version(): int
    {
        return rand();
    }

    /**
     * Implement this method to set the priority of a script.
     * Scripts with higher priority are ran before for the same application version.
     *
     * @return int
     */
    public function priority(): int
    {
        return rand();
    }
}