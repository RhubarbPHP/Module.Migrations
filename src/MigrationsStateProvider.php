<?php


namespace Rhubarb\Scaffolds\Migrations;


use Rhubarb\Crown\Application;
use Rhubarb\Crown\DependencyInjection\ProviderInterface;
use Rhubarb\Crown\DependencyInjection\ProviderTrait;

abstract class MigrationsStateProvider implements ProviderInterface
{
    use ProviderTrait;

    /** @var int $localVersion */
    private $localVersion;
    /** @var string $resumeScript */
    private $resumeScript;

    /**
     * @return int
     */
    abstract public function getLocalVersion(): int;

    abstract public function setLocalVersion(int $newLocalVersion): void;

    abstract public function getResumeScript(): string;

    abstract public function setResumeScript(): void;

    public function getApplicationVersion(): int
    {
        return Application::current()->getVersion();
    }
}