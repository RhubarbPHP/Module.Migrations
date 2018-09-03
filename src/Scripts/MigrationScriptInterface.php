<?php


namespace Rhubarb\Modules\Migrations\Scripts;


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
    public static function version(): int;

    /**
     * Implement this method to set the priority of a script.
     * Scripts with higher priority are ran before for the same application version.
     *
     * @return int
     */
    public static function priority(): int;
}