<?php

namespace Rhubarb\Modules\Migrations\UseCases;

use Error;
use Rhubarb\Crown\Logging\Log;
use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;
use Rhubarb\Modules\Migrations\MigrationsManager;
use Rhubarb\Modules\Migrations\MigrationsStateProvider;

class RunMigrationsUseCase
{
    /**
     * @param int $start
     * @param int $end
     * @param array $skip
     */
    public static function execute(int $start, int $end, array $skip = [])
    {
        Log::info("Beginning migration from $start to $end");
        Log::indent();
        self::executeMigrationScripts(self::getMigrationScripts($start, $end, $skip));
        self::updateLocalVersionOnCompletion($end);
        Log::outdent();
        Log::info("Finished migration from $start  to $end");
    }

    /**
     * @param array $migrationScripts
     */
    private static function executeMigrationScripts(array $migrationScripts)
    {
        foreach ($migrationScripts as $migrationScript) {
            try {
                $scriptClass = get_class($migrationScript);
                Log::info("Executing script $scriptClass at version {$migrationScript->version()}");
                self::beforeScriptExecution($migrationScript);
                $migrationScript->execute();
                self::afterSuccessfulScriptExecution($migrationScript);
            } catch (Error $error) {
                self::afterFailedScriptExecution($migrationScript, $error);
                Log::outdent();
                Log::error("Failed migration at script $scriptClass");
                throw $error;
            }
        }
    }

    /**
     * @param MigrationScriptInterface $migrationScript
     */
    protected static function beforeScriptExecution(MigrationScriptInterface $migrationScript)
    {

    }

    /**
     * @param MigrationScriptInterface $migrationScript
     */
    protected static function afterSuccessfulScriptExecution(MigrationScriptInterface $migrationScript)
    {
        self::markScriptCompleted($migrationScript);
        self::updateLocalVersion($migrationScript->version());
    }

    /**
     * @param MigrationScriptInterface $migrationScript
     * @param Error $error
     */
    protected static function afterFailedScriptExecution(MigrationScriptInterface $migrationScript, Error $error)
    {

    }

    /**
     * @param MigrationScriptInterface $migrationScript
     */
    protected static function markScriptCompleted(MigrationScriptInterface $migrationScript)
    {
        MigrationsStateProvider::getProvider()->markScriptCompleted($migrationScript);
    }

    /**
     * @param $end
     */
    protected static function updateLocalVersionOnCompletion($end)
    {
        self::updateLocalVersion($end);
    }

    /**
     * @param int $updatedVersion
     */
    private static function updateLocalVersion(int $newLocalVersion)
    {
        MigrationsStateProvider::getProvider()->setLocalVersion($newLocalVersion);
    }

    /**
     * Retrieve the registered migration scripts relevant to the current migration range.
     *
     * @param int $start
     * @param int $end
     * @param array $skip
     * @return MigrationScriptInterface[]
     */
    private static function getMigrationScripts(int $start, int $end, array $skip)
    {
        return MigrationsManager::getMigrationsManager()->getMigrationScripts(
            $start,
            $end,
            $skip
        );
    }
}