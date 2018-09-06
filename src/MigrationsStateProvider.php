<?php


namespace Rhubarb\Modules\Migrations;


use Rhubarb\Crown\Application;
use Rhubarb\Crown\DependencyInjection\ProviderInterface;
use Rhubarb\Crown\DependencyInjection\ProviderTrait;
use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;
use Rhubarb\Modules\Migrations\UseCases\MigrationEntity;

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

    /**
     * Updates the Start, End and Priority points on the Migration Entity to change which scripts get ran.
     *
     * @param MigrationEntity $entity
     */
    public function applyResumePoint(MigrationEntity $entity): void
    {
        // No default behaviour, nor a demand that it be implemented.
    }

    /**
     * @param MigrationScriptInterface $failingScript
     */
    public function storeResumePoint(MigrationScriptInterface $failingScript) {

    }

    public function getApplicationVersion(): int
    {
        return Application::current()->getVersion();
    }
}