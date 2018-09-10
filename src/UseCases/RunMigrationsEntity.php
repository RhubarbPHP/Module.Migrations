<?php


namespace Rhubarb\Modules\Migrations\UseCases;

use Rhubarb\Modules\Migrations\MigrationsStateProvider;
use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;

class RunMigrationsEntity
{
    /** @var int $startVersion */
    public $startVersion;
    /** @var int $endVersion */
    public $endVersion;

    /** @var string[] $skipScripts */
    public $skipScripts = [];

    /** @var MigrationScriptInterface[] $migrationScripts */
    public $migrationScripts = [];

    public function __construct()
    {
        $this->startVersion = MigrationsStateProvider::getProvider()->getLocalVersion();
        $this->endVersion = MigrationsStateProvider::getApplicationVersion();
    }
}