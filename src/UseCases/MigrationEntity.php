<?php


namespace Rhubarb\Modules\Migrations\UseCases;


use Rhubarb\Modules\Migrations\MigrationsManager;
use Rhubarb\Modules\Migrations\MigrationsSettings;

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