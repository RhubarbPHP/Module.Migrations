<?php


namespace Rhubarb\Scaffolds\Migrations\UseCases;


use Rhubarb\Scaffolds\Migrations\MigrationsManager;
use Rhubarb\Scaffolds\Migrations\MigrationsSettings;

class MigrationEntity
{
    /** @var int $targetVersion */
    public $targetVersion;
    /** @var int $localVersion */
    public $localVersion;
    /** @var string $resumeScript */
    public $resumeScript;
    /** @var string[] $skipScripts */
    public $skipScripts = [];

    public function __construct()
    {
        $this->localVersion = MigrationsSettings::singleton()->getLocalVersion();
    }
}