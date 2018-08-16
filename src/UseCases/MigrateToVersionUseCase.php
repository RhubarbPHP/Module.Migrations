<?php


namespace Rhubarb\Scaffolds\Migrations\UseCases;


use PHPUnit\Runner\Exception;
use Rhubarb\Crown\Logging\Log;
use Rhubarb\Scaffolds\Migrations\MigrationsManager;
use Rhubarb\Scaffolds\Migrations\MigrationsSettings;
use Rhubarb\Scaffolds\Migrations\Scripts\MigrationScript;

class MigrateToVersionUseCase
{
    /**
     * @param int $targetVersion
     * @throws \Rhubarb\Crown\Exceptions\ImplementationException
     */
    public static function execute(MigrationEntity $entity)
    {
        Log::info("Beginning migration from $$entity->localVersion to $entity->targetVersion");
        Log::indent();
        try {
            self::executeMigrationScripts(self::getMigrationScripts($entity));
            self::updateLocalVersion($entity->targetVersion);
        } catch (Exception $exception) {
            Log::error("Failed migration from $entity->localVersion  to $entity->targetVersion");
            Log::outdent();
            throw $exception;
        }
        Log::info("Finished migration from $entity->localVersion  to $entity->targetVersion");
        Log::outdent();
    }

    /**
     * @param MigrationScript[] $migrationScripts
     */
    private static function executeMigrationScripts($migrationScripts)
    {
        foreach ($migrationScripts as $migrationScript) {
            try {
                $scriptClass = get_class($migrationScript);
                Log::info("Executing Script $scriptClass for version {$migrationScript->version()} with priority {$migrationScript->priority()}");
                $migrationScript->execute();
            } catch (Exception $exception) {
                Log::error($exception->getMessage(), "", $exception->getTrace());
                throw $exception;
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
     * @return MigrationScript[] array
     * @throws \Rhubarb\Crown\Exceptions\ImplementationException
     */
    private static function getMigrationScripts(MigrationEntity $entity): array
    {
        $scripts = MigrationsManager::getMigrationsManager()->getMigrationScripts();

        $lower = $entity->localVersion;
        /** @var MigrationScript $resume */
        if ($entity->resumeScript && is_a($resume = new $entity->resumeScript(), MigrationScript::class)) {
            $migrationScripts[] = $resume;
            $lower = $resume->version();
        }

        foreach ($scripts as $script) {
            if (
                in_array(get_class($script), $entity->skipScripts)
                || (isset($migrationScripts) && in_array($script, $migrationScripts))
            ) {
                continue;
            }
            if (
                $script->version() >= $lower
                && $script->version() <= $entity->targetVersion
            ) {
                $migrationScripts[] = $script;
            }
        }

        if (!isset($migrationScripts)) {
            return [];
        }

        usort($migrationScripts, function (MigrationScript $a, MigrationScript $b) {
            if ($a->version() != $b->version()) {
                return $a->version() <=> $b->version();
            } else {
                return $b->priority() <=> $a->priority();
            }
        });

        return $migrationScripts;
    }
}