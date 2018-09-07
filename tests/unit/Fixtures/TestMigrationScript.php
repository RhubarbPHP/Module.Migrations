<?php


namespace Rhubarb\Modules\Migrations\Tests\Fixtures;


use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;

class TestMigrationScript implements MigrationScriptInterface
{
    public $executeMethod = null;

    /**
     * Primary logic of the script should be implemented or called here.
     *
     * @return mixed
     */
    public function execute()
    {
        if (is_callable($this->executeMethod)) {
            return ($this->executeMethod)();
        }
        return $this->executeMethod;
    }

    /**
     * The application version this script should be ran on
     *
     * @return int
     */
    public function version(): int
    {
        return 6;
    }

    /**
     * Implement this method to set the priority of a script.
     * Scripts with higher priority are ran before for the same application version.
     *
     * @return int
     */
    public function priority(): int
    {
        return 1;
    }
}