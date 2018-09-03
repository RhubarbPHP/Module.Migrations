<?php


namespace Rhubarb\Modules\Migrations;


use Rhubarb\Crown\Application;
use Rhubarb\Crown\DependencyInjection\SingletonTrait;
use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Modules\Migrations\Scripts\MigrationScriptInterface;

class MigrationsManager
{
    use SingletonTrait;

    /** @var string[] $migrationScriptClasses */
    protected $migrationScriptClasses = [];

    /**
     *
     * @param int|null $minVersion
     * @param int|null $maxVersion
     * @return MigrationScriptInterface[]
     * @throws ImplementationException
     */
    public function getMigrationScripts(int $minVersion = null, int $maxVersion = null): array
    {
        /** @var MigrationScriptInterface $migrationScriptClass */
        foreach ($this->getMigrationScriptClasses() as $migrationScriptClass) {
            if (class_exists($migrationScriptClass)) {
                if (
                    (isset($minVersion) && $migrationScriptClass::version() < $minVersion)
                    ||
                    (isset($maxVersion) && $migrationScriptClass::version() > $maxVersion)
                ) {
                    continue;
                }
                $migrationScript = new $migrationScriptClass();
                if (is_a($migrationScript, MigrationScriptInterface::class)) {
                    $migrationScripts[] = $migrationScript;
                } else {
                    throw new ImplementationException('Non-MigrationScript Class provided to MigrationManager');
                }
            } else {
                throw new ImplementationException('Non-Existent MigrationScript provided to MigrationManager.');
            }
        }

        return $migrationScripts ?? [];
    }

    /**
     * @return string[]
     */
    protected function getMigrationScriptClasses()
    {
        return $this->migrationScriptClasses;
    }

    /**
     * Call this method in the application to define which MigrationScripts should be loaded/processed.
     * Alternatively, extend this method to avoid cluttering the Application class.
     *
     * @param array $migrationScriptClasses
     */
    public function registerMigrationScripts(array $migrationScriptClasses)
    {
        $this->migrationScriptClasses = $migrationScriptClasses;
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