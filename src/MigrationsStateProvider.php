<?php


namespace Rhubarb\Modules\Migrations;


use Rhubarb\Crown\Application;
use Rhubarb\Crown\DependencyInjection\ProviderInterface;
use Rhubarb\Crown\DependencyInjection\ProviderTrait;
use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;
use Rhubarb\Modules\Migrations\UseCases\RunMigrationsEntity;
use Rhubarb\Modules\Migrations\UseCases\RunMigrationsUseCase;

abstract class MigrationsStateProvider implements ProviderInterface
{
    use ProviderTrait;

    /** @var int $localVersion */
    protected $localVersion;

    /**
     * @return int
     */
    abstract public function getLocalVersion(): int;

    /**
     * @param int $newLocalVersion
     */
    abstract public function setLocalVersion(int $newLocalVersion): void;

    abstract public function markScriptCompleted(MigrationScriptInterface $migrationScript): void;

    abstract public function isScriptComplete(string $className): bool;

    abstract public function getCompletedScripts(): array;

    public static function getApplicationVersion(): int
    {
        return Application::current()->getVersion();
    }

    public function runMigrations(RunMigrationsEntity $entity)
    {
        RunMigrationsUseCase::execute($entity);
    }
}