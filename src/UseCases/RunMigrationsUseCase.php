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
                self::updateLocalVersion($migrationScript);
            } catch (Error $error) {
                self::storeResumePoint($migrationScript);
                Log::outdent();
                Log::error("Failed migration from $entity->startVersion to $entity->endVersion at script $scriptClass");
                throw $error;
            }
        }
    }

    protected static function storeResumePoint(MigrationScriptInterface $failingScript)
    {
        MigrationsStateProvider::getProvider()->storeResumePoint($failingScript);
    }

    /**
     * @param int $updatedVersion
     */
    private static function updateLocalVersion(MigrationScriptInterface $completedScript)
    {
        MigrationsStateProvider::getProvider()->setLocalVersion($completedScript->version());
    }

    /**
     * @param int $currentVersion
     * @param int $targetVersion
     * @return MigrationScriptInterface[] array
     */
    private static function getMigrationScripts(MigrationEntity $entity): array
    {
        if ($entity->resume) {
            MigrationsStateProvider::getProvider()->applyResumePoint($entity);
        }

        return MigrationsManager::getMigrationsManager()->getMigrationScripts($entity);
    }
}