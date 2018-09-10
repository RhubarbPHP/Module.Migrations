<?php


namespace Rhubarb\Modules\Migrations\Interfaces;


interface MigrationScriptInterface
{
    /**
     * Primary logic of the script should be implemented or called here.
     */
    public function execute();

    /**
     * The application version this script should be ran on
     *
     * @return int
     */
    public function version(): int;
}