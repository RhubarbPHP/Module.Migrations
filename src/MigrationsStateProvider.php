<?php


namespace Rhubarb\Modules\Migrations;


use Rhubarb\Crown\Application;
use Rhubarb\Crown\DependencyInjection\ProviderInterface;
use Rhubarb\Crown\DependencyInjection\ProviderTrait;

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

    public function getApplicationVersion(): int
    {
        return Application::current()->getVersion();
    }
}