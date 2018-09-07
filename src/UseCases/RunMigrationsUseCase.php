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
    public static function execute(MigrationEntity $entity)
    {
        Log::info("Beginning migration from $$entity->startVersion to $entity->endVersion");
        Log::indent();
        self::getMigrationScripts($entity);
        self::executeMigrationScripts($entity);
        if ($entity->endVersion) {
            self::updateLocalVersionOnCompletion($entity);
        }
        Log::outdent();
        Log::info("Finished migration from $entity->startVersion  to $entity->endVersion");
    }

    /**
     * @param MigrationEntity $entity
     */
    private static function executeMigrationScripts(MigrationEntity $entity)
    {
        foreach ($entity->migrationScripts as $migrationScript) {
            try {
                $scriptClass = get_class($migrationScript);
                Log::info("Executing Script $scriptClass for version {$migrationScript->version()} with priority {$migrationScript->priority()}");
                $migrationScript->execute();
                self::updateLocalVersionForScript($migrationScript);
            } catch (Error $error) {
                self::storeResumePoint($migrationScript);
                Log::outdent();
                Log::error("Failed migration from $entity->startVersion to $entity->endVersion at script $scriptClass");
                throw $error;
            }
        }
    }

    /**
     * @param MigrationScriptInterface $failingScript
     */
    protected static function storeResumePoint(MigrationScriptInterface $failingScript)
    {
        MigrationsStateProvider::getProvider()->storeResumePoint($failingScript);
    }

    /**
     * @param MigrationEntity $entity
     */
    protected static function updateLocalVersionOnCompletion(MigrationEntity $entity)
    {
        self::updateLocalVersion($entity->endVersion);
    }

    /**
     * @param MigrationScriptInterface $migrationScript
     */
    protected static function updateLocalVersionForScript(MigrationScriptInterface $migrationScript)
    {
        self::updateLocalVersion($migrationScript->version());
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
    private static function getMigrationScripts(MigrationEntity $entity)
    {
        if ($entity->resume) {
            MigrationsStateProvider::getProvider()->applyResumePoint($entity);
        }
        MigrationsManager::getMigrationsManager()->getMigrationScripts($entity);
    }
}