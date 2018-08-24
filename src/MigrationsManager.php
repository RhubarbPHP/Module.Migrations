<?php


namespace Rhubarb\Scaffolds\Migrations;


use Rhubarb\Crown\Application;
use Rhubarb\Crown\DependencyInjection\SingletonTrait;
use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Scaffolds\Migrations\Scripts\MigrationScriptInterface;

class MigrationsManager
{
    use SingletonTrait;

    /** @var string[] $migrationScriptClasses */
    protected $migrationScriptClasses = [];

    /** @var MigrationScriptInterface[] $migrationScripts */
    protected $migrationScripts = [];

    /** @var boolean $scriptsInstantiated */
    protected $scriptsInstantiated = false;

    /**
     * @return MigrationScriptInterface[]
     * @throws ImplementationException
     */
    public function getMigrationScripts(): array
    {
        if (!$this->scriptsInstantiated) {
            $this->instantiateMigrationScripts();
        }

        foreach ($this->migrationScripts as $migrationScript) {
            if (is_a($migrationScript, MigrationScriptInterface::class)) {
                $migrationScripts[] = $migrationScript;
            } else {
                throw new ImplementationException('Non-MigrationScript Class provided to MigrationManager');
            }
        }

        return $migrationScripts ?? [];
    }

    /**
     * Instantiates the register migration script classes.
     *
     * @throws ImplementationException
     */
    private function instantiateMigrationScripts()
    {
        foreach ($this->getMigrationScriptClasses() as $migrationScriptClass) {
            if (class_exists($migrationScriptClass)) {
                $migrationScripts[] = new $migrationScriptClass();
            } else {
                throw new ImplementationException('Non-Existent MigrationScript provided to MigrationManager.');
            }
        }

        $this->migrationScripts = $migrationScripts ?? [];
        $this->scriptsInstantiated = true;
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
        $this->scriptsInstantiated = false;
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