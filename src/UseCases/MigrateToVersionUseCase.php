<?php


namespace Rhubarb\Scaffolds\Migrations\UseCases;

use Error;
use Exception;
use Rhubarb\Crown\Logging\Log;
use Rhubarb\Scaffolds\Migrations\MigrationsManager;
use Rhubarb\Scaffolds\Migrations\MigrationsSettings;
use Rhubarb\Scaffolds\Migrations\Scripts\MigrationScriptInterface;

class MigrateToVersionUseCase
{
    /**
     * @param int $targetVersion
     * @throws \Rhubarb\Crown\Exceptions\ImplementationException|Exception
     */
    public static function execute(MigrationEntity $entity)
    {
        Log::info("Beginning migration from $$entity->localVersion to $entity->targetVersion");
        Log::indent();
        try {
            self::executeMigrationScripts(self::getMigrationScripts($entity));
            self::updateLocalVersion($entity->targetVersion);
        } catch (Error $error) {
            Log::outdent();
            Log::error("Failed migration from $entity->localVersion  to $entity->targetVersion");
        }
        Log::outdent();
        Log::info("Finished migration from $entity->localVersion  to $entity->targetVersion");
    }

    /**
     * @param MigrationScriptInterface[] $migrationScripts
     */
    private static function executeMigrationScripts($migrationScripts)
    {
        foreach ($migrationScripts as $migrationScript) {
            try {
                $scriptClass = get_class($migrationScript);
                Log::info("Executing Script $scriptClass for version {$migrationScript->version()} with priority {$migrationScript->priority()}");
                $migrationScript->execute();
            } catch (Error $error) {
                MigrationsSettings::singleton()->setResumeScript(get_class($migrationScript));
                throw $error;
            }
        }
    }

    /**
     * @param int $updatedVersion
     */
    private static function updateLocalVersion(int $updatedVersion)
    {
        MigrationsSettings::singleton()->setLocalVersion($updatedVersion);
    }

    /**
     * @param int $currentVersion
     * @param int $targetVersion
     * @return MigrationScriptInterface[] array
     * @throws \Rhubarb\Crown\Exceptions\ImplementationException
     */
    private static function getMigrationScripts(MigrationEntity $entity): array
    {
        $scripts = MigrationsManager::getMigrationsManager()->getMigrationScripts();

        foreach ($scripts as $script) {
            if (
                in_array(get_class($script), $entity->skipScripts)
                || (isset($migrationScripts) && in_array($script, $migrationScripts))
            ) {
                continue;
            }
            if (
                $script->version() >= $entity->localVersion
                && $script->version() <= $entity->targetVersion
            ) {
                $migrationScripts[] = $script;
            }
        }

        if (!isset($migrationScripts)) {
            return [];
        }

        usort($migrationScripts, function (MigrationScriptInterface $a, MigrationScriptInterface $b) {
            if ($a->version() != $b->version()) {
                return $a->version() <=> $b->version();
            } else {
                return $b->priority() <=> $a->priority();
            }
        });

        /** @var MigrationScriptInterface $resume */
        if ($entity->resumeScript && is_a($resume = new $entity->resumeScript(), MigrationScriptInterface::class)) {
            $key = array_search($entity->resumeScript, array_map('get_class', $migrationScripts));
            array_splice($migrationScripts, 0, $key);
        }

        return $migrationScripts;
    }
}