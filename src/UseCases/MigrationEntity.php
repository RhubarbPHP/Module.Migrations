<?php


namespace Rhubarb\Modules\Migrations\UseCases;


use Rhubarb\Modules\Migrations\MigrationsManager;
use Rhubarb\Modules\Migrations\MigrationsSettings;
use Rhubarb\Modules\Migrations\MigrationsStateProvider;

class MigrationEntity
{
    /** @var int $targetVersion */
    public $targetVersion;
    /** @var int $startVersion */
    public $startVersion;
    /** @var bool $attemptResume */
    public $attemptResume = false;
    /** @var string $resumeScript */
    public $resumeScript;
    /** @var string[] $skipScripts */
    public $skipScripts = [];

    public function __construct()
    {
        $this->startVersion = MigrationsStateProvider::getProvider()->getLocalVersion();
    }
}