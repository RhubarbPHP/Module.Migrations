<?php


namespace Rhubarb\Modules\Migrations\UseCases;


use Rhubarb\Modules\Migrations\MigrationsManager;
use Rhubarb\Modules\Migrations\MigrationsSettings;
use Rhubarb\Modules\Migrations\MigrationsStateProvider;
use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;

class MigrationEntity
{
    /** @var int $startVersion */
    public $startVersion;
    /** @var int $endVersion */
    public $endVersion;

    /** @var int $startPriority */
    public $startPriority;
    /** @var int $endPriority  */
    public $endPriority;

    /** @var bool $resume */
    public $resume = false;
    /** @var string[] $skipScripts */
    public $skipScripts = [];

    /** @var MigrationScriptInterface[] $migrationScripts */
    public $migrationScripts = [];

    public function __construct()
    {
        // Default Values
        $this->startVersion = MigrationsStateProvider::getProvider()->getLocalVersion();
    }
}