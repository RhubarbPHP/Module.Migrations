<?php


namespace Rhubarb\Modules\Migrations\Scripts;


use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;

abstract class MigrationScript implements MigrationScriptInterface
{
    /** @var int $version */
    private $version;
    /** @var int $priority */
    private $priority;

    public final function __construct($version, $priority = 0)
    {
        $this->version = $version;
        $this->priority = $priority;
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

    /**
     * Implement this method to set the priority of a script.
     * Scripts with higher priority are ran before for the same application version.
     *
     * @return int
     */
    public function priority(): int
    {
        return $this->priority();
    }
}