<?php /** @noinspection PhpIncludeInspection */
/** @noinspection PhpUndefinedClassInspection */

/** @noinspection PhpUndefinedMethodInspection */

class ExampleApplication extends \Rhubarb\Crown\Application
{
    protected function initialise()
    {
        // ...

        $this->version = 18;

        /**
         * Set Migration Settings to use a specific path and file name.
         * By default the system temp directory will be used.
         */
        $migrationSettings = MigrationsSettings::singleton();
        // Local Version is used to determine which scripts have already been ran. It is compared against $version on the Application.
        $migrationSettings->setLocalVersionPath(__DIR__ . '../settings/local-version.lock');
        // Resume Script is used to
        $migrationSettings->setResumeScriptPath(__DIR__ . '../settings/resume-script.lock');
        // When iterating over large tables this setting will be used to page the dataset. Default is 100.
        $migrationSettings->pageSize = 10000;
        // Informs DataMigrations which repository you are using. Default is MySql.
        $migrationSettings->repositoryType = MySql::class;

        // This is how you inform the Migrations Manager which scripts you want to be run when the migrate command is run.
        // If this list is long you can extend the Migrations Manager and include the script there.
        MigrationsManager::getMigrationsManager()->registerMigrationScripts([
            TestingMigrations::class,
            TestingPriority::class,
            TestingVersion::class
        ]);

        // ...
    }

    protected function getModules()
    {
        return [
            new LayoutModule(DefaultLayout::class),
            new LeafModule(),
            new StemModule(),
            new MigrationsModule(),
        ];
    }
}