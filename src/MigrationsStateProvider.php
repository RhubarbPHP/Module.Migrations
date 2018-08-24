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

    /**
     * @param int $newLocalVersion
     */
    abstract public function setLocalVersion(int $newLocalVersion): void;

    /**
     * @return string
     */
    abstract public function getResumeScript(): string;

    /**
     * @param $newResumeScript
     */
    abstract public function setResumeScript($newResumeScript): void;

    public function getApplicationVersion(): int
    {
        return Application::current()->getVersion();
    }
}