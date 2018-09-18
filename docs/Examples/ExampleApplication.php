<?php
/** @noinspection PhpIncludeInspection */
/** @noinspection PhpUndefinedClassInspection */
/** @noinspection PhpUndefinedMethodInspection */

class ExampleApplication extends \Rhubarb\Crown\Application
{
    protected function initialise()
    {
        // ...
        $this->version = 18;

        // Register the MigrationScripts to be checked and executed when the migrations:migrate command is ran.
        MigrationsManager::getMigrationsManager()->registerMigrationScripts([
            new MoveAvatarLocations(),
            new DeleteOldImages(),
            new ClearLogs()
        ]);
        // ...
    }

    protected function getModules()
    {
        return [
            new LayoutModule(DefaultLayout::class),
            new LeafModule(),
            // ...
            // To use a custom migration state provider supply its class when adding the migrations module
            new MigrationsModule(CustomMigrationStatProvider::class),
            // ...
        ];
    }
}