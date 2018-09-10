<?php


namespace Rhubarb\Modules\Migrations\Tests\Fixtures;


use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;

class TestMigrationScript implements MigrationScriptInterface
{
    private $execute;
    private $version;

    public function __construct(int $version, $execute = null)
    {
        $this->version = $version;
        $this->execute = $execute;
    }


    /**
     * Primary logic of the script should be implemented or called here.
     *
     * @return mixed
     */
    public function execute()
    {
        if (is_callable($this->execute)) {
            return ($this->execute)();
        }
        return $this->execute;
    }

    /**
     * The application version this script should be ran on
     *
     * @return int
     */
    public function version(): int
    {
        return $this->version;
    }
}