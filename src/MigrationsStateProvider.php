<?php


namespace Rhubarb\Modules\Migrations;


use Rhubarb\Crown\Application;
use Rhubarb\Crown\DependencyInjection\ProviderInterface;
use Rhubarb\Crown\DependencyInjection\ProviderTrait;
use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;
use Rhubarb\Modules\Migrations\UseCases\RunMigrationsUseCase;

abstract class MigrationsStateProvider implements ProviderInterface
{
    use ProviderTrait;

    /** @var int $localVersion */
    protected $localVersion;

    /**
     * Returns the locally stored version number of the application based on when migrations were last run.
     *
     * @return int
     */
    abstract public function getLocalVersion(): int;

    /**
     * Updates the locally stored version number.
     *
     * @param int $newLocalVersion
     */
    abstract public function setLocalVersion(int $newLocalVersion): void;

    /**
     * Locally stores a MigrationScript as having been successfully executed.
     *
     * @param MigrationScriptInterface $migrationScript
     */
    abstract public function markScriptCompleted(MigrationScriptInterface $migrationScript): void;

    /**
     * Checks if a migration script has already been successfully executed locally.
     *
     * @param string $className
     * @return bool
     */
    abstract public function isScriptComplete(string $className): bool;

    /**
     * Returns all migration scripts which have been run on the local application.
     *
     * @return array
     */
    abstract public function getCompletedScripts(): array;

    /**
     * Returns the target version number of the application as defined in the application class.
     *
     * @return int
     */
    public static function getApplicationVersion(): int
    {
        return Application::current()->getVersion();
    }

    /**
     * Allows additional logic to be added before or after migration execution without altering the use case.
     *
     * @param int $start
     * @param int $end
     * @param array $skip
     */
    public function runMigrations(int $start, int $end, array $skip = [])
    {
        RunMigrationsUseCase::execute($start, $end, $skip);
    }
}