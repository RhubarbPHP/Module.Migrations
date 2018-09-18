<?php


namespace Rhubarb\Modules\Migrations;


use Rhubarb\Crown\Application;
use Rhubarb\Crown\DependencyInjection\ProviderInterface;
use Rhubarb\Crown\DependencyInjection\ProviderTrait;
use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;
use Rhubarb\Modules\Migrations\UseCases\RunMigrationsUseCase;

abstract class MigrationsStateProvider implements ProviderInterface
{
    use ProviderTrait;

    /** @var int $localVersion */
    protected $localVersion;


    abstract public function getLocalVersion(): int;
    abstract public function setLocalVersion(int $newLocalVersion): void;

    abstract public function markScriptCompleted(MigrationScriptInterface $migrationScript): void;
    abstract public function isScriptComplete(string $className): bool;

    abstract public function getCompletedScripts(): array;

    public static function getApplicationVersion(): int
    {
        return Application::current()->getVersion();
    }

    public function runMigrations(int $start, int $end, array $skip = [])
    {
        RunMigrationsUseCase::execute($start, $end, $skip);
    }
}