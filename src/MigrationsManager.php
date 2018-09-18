<?php


namespace Rhubarb\Modules\Migrations;


use Rhubarb\Crown\Application;
use Rhubarb\Crown\DependencyInjection\SingletonTrait;
use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;

class MigrationsManager
{
    use SingletonTrait;

    private $migrationScripts = [];

    /**
     * Returns an ordered array of MigrationScriptInterface objects within the range specified.
     *
     * @param int $from
     * @param int $to
     * @param string[] $skipScripts
     * @return MigrationScriptInterface[]
     */
    public function getMigrationScripts(int $from, int $to, array $skipScripts = []): array
    {
        $migrationScripts = [];
        $provider = MigrationsStateProvider::getProvider();
        foreach ($this->migrationScripts as $migrationScript) {
            $scriptClass = get_class($migrationScript);
            if (!is_a($migrationScript, MigrationScriptInterface::class)) {
                throw new \Error('Non-MigrationScriptInterface object provided to MigrationManager:' . $migrationScript);
            }
            if (
                $migrationScript->version() < $from
                || $migrationScript->version() > $to
                || array_search($scriptClass, $skipScripts) !== false
            ) {
                continue;
            }
            if ($provider->isScriptComplete($scriptClass)) {
                continue;
            }
            $migrationScripts[] = $migrationScript;
        }
        usort($migrationScripts, function (MigrationScriptInterface $a, MigrationScriptInterface $b) {
            return $a->version() <=> $b->version();
        });

        return $migrationScripts;
    }

    /**
     * @return string[]
     */
    public function getRegisteredMigrationScriptClasses(): array
    {
        $classes = [];
        foreach ($this->migrationScripts as $migrationScript) {
            $classes[] = get_class($migrationScript);
        }
        return $classes;
    }

    /**
     * Defines which MigrationScripts should be checked when running migrations.
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