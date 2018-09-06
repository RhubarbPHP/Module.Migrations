<?php


namespace Rhubarb\Modules\Migrations;


use Rhubarb\Crown\Application;
use Rhubarb\Crown\DependencyInjection\SingletonTrait;
use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;
use Rhubarb\Modules\Migrations\UseCases\MigrationEntity;

class MigrationsManager
{
    use SingletonTrait;

    private $migrationScripts = [];

    /**
     * @param int|null $minVersion
     * @param int|null $maxVersion
     */
    public function getMigrationScripts(MigrationEntity $entity)
    {
        foreach ($this->migrationScripts as $migrationScript) {
            if (!is_a($migrationScript, MigrationScriptInterface::class)) {
                throw new \Error('Non-MigrationScriptInterface object provided to MigrationManager:' . get_class($migrationScript));
            }
            if (!$this->isScriptInRange($migrationScript, $entity)) {
                continue;
            }

            $entity->migrationScripts[] = $migrationScript;
        }
        $this->sortMigrationScripts($entity->migrationScripts);
    }

    /**
     * @param MigrationScriptInterface $migrationScript
     * @param MigrationEntity          $entity
     * @return bool
     */
    protected function isScriptInRange(MigrationScriptInterface $migrationScript, MigrationEntity $entity)
    {
        if (
            (isset($entity->startVersion) && $migrationScript->version() < $entity->startVersion)
            || (isset($entity->startPriority) && $migrationScript->priority() < $entity->startPriority)
            || (isset($entity->endVersion) && $migrationScript->version() > $entity->endVersion)
            || (isset($entity->endPriority) && $migrationScript->priority() > $entity->endPriority)
            || (array_search(get_class($migrationScript), $entity->skipScripts))
        ) {
            return false;
        }
        return true;
    }

    /**
     * @param MigrationScriptInterface[] $migrationScripts
     * @return MigrationScriptInterface[]
     */
    protected function sortMigrationScripts(&$migrationScripts): array
    {
        usort($migrationScripts, function (MigrationScriptInterface $a, MigrationScriptInterface $b) {
            if ($a->version() != $b->version()) {
                return $a->version() <=> $b->version();
            } else {
                return $b->priority() <=> $a->priority();
            }
        });
        return $migrationScripts;
    }

    /**
     * Call this method in the application to define which MigrationScripts should be loaded/processed.
     * Alternatively, extend this method to avoid cluttering the Application class.
     *
     * @param MigrationScriptInterface[] $migrationScripts
     */
    public function registerMigrationScripts(array $migrationScripts)
    {
        $this->migrationScripts = $migrationScripts;
    }

    /**
     * Used to override the generic migration manager with a custom one.
     */
    public static function registerMigrationManager(string $migrationManagerClass)
    {
        Application::current()->container()->registerSingleton(MigrationsManager::class, function () use (
            $migrationManagerClass
        ) {
            return $migrationManagerClass::singleton();
        });
    }

    /**
     * @return MigrationsManager
     */
    public static function getMigrationsManager(): MigrationsManager
    {
        return Application::current()->container()->getSingleton(MigrationsManager::class);
    }
}