<?php
/** @noinspection PhpUndefinedMethodInspection */

/** @noinspection PhpUndefinedClassInspection */

use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;

class ExampleMigrationScript implements MigrationScriptInterface
{

    /**
     * Primary logic of the script should be implemented or called here.
     */
    public function execute()
    {
        foreach (scandir('../assets/images') as $imageFile) {
            file_put_contents('../content/images/new', file_get_contents($imageFile));
            unlink($imageFile);
        }
    }

    /**
     * The application version this script should be ran on
     *
     * @return int
     */
    public function version(): int
    {
        return 17;
    }

}