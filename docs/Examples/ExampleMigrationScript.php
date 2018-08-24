<?php /** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUndefinedClassInspection */

use Rhubarb\Scaffolds\Migrations\Scripts\MigrationScriptInterface;

class ExampleMigrationScript implements MigrationScriptInterface
{

    /**
     * Primary logic of the script should be implemented or called here.
     */
    public function execute()
    {
        foreach (Image::find(new Equals('active', false)) as $image) {
            unlink($image->filePath);
            $image->delete();
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

    /**
     * Implement this method to set the priority of a script.
     * Scripts with higher priority are ran before for the same application version.
     *
     * @return int
     */
    public function priority(): int
    {
        return 0;
    }
}