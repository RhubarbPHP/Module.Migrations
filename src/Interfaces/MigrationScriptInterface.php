<?php


namespace Rhubarb\Modules\Migrations\Interfaces;


interface MigrationScriptInterface
{
    /**
     * The logic of a migration is implemented through this method
     */
    public function execute();

    /**
     * Used to determine on which application version to execute this script
     *
     * @return int
     */
    public function version(): int;
}