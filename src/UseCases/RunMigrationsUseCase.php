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
     * @param int $targetVersion
     * @throws Error
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

    protected static function beforeScriptExecution(MigrationScriptInterface $migrationScript)
    {

    }

    protected static function afterSuccessfulScriptExecution(MigrationScriptInterface $migrationScript)
    {
        self::markScriptCompleted($migrationScript);
        self::updateLocalVersion($migrationScript->version());
    }

    protected static function afterFailedScriptExecution(MigrationScriptInterface $migrationScript, Error $error)
    {

    }

    protected static function markScriptCompleted(MigrationScriptInterface $migrationScript)
    {
        MigrationsStateProvider::getProvider()->markScriptCompleted($migrationScript);
    }

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
     * @param int $currentVersion
     * @param int $targetVersion
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