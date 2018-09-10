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
    public static function execute(RunMigrationsEntity $entity)
    {
        Log::info("Beginning migration from $$entity->startVersion to $entity->endVersion");
        Log::indent();
        self::getMigrationScripts($entity);
        self::executeMigrationScripts($entity);
        self::updateLocalVersionOnCompletion($entity);
        Log::outdent();
        Log::info("Finished migration from $entity->startVersion  to $entity->endVersion");
    }

    /**
     * @param RunMigrationsEntity $entity
     */
    private static function executeMigrationScripts(RunMigrationsEntity $entity)
    {
        foreach ($entity->migrationScripts as $migrationScript) {
            try {
                $scriptClass = get_class($migrationScript);
                Log::info("Executing script $scriptClass at version {$migrationScript->version()}");
                self::beforeScriptExecution($migrationScript);
                $migrationScript->execute();
                self::afterSuccessfulScriptExecution($migrationScript);
            } catch (Error $error) {
                self::afterFailedScriptExecution($migrationScript, $error);
                Log::outdent();
                Log::error("Failed migration from $entity->startVersion to $entity->endVersion at script $scriptClass");
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

    /**
     * @param RunMigrationsEntity $entity
     */
    protected static function updateLocalVersionOnCompletion(RunMigrationsEntity $entity)
    {
        self::updateLocalVersion($entity->endVersion);
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
    private static function getMigrationScripts(RunMigrationsEntity $entity)
    {
        $entity->migrationScripts =
            MigrationsManager::getMigrationsManager()->getMigrationScripts(
                $entity->startVersion,
                $entity->endVersion,
                $entity->skipScripts
            );
    }
}