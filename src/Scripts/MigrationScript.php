<?php

namespace Rhubarb\Modules\Migrations\Scripts;

use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;

abstract class MigrationScript implements MigrationScriptInterface
{
    /** @var int $version */
    private $version;

    public final function __construct($version)
    {
        $this->version = $version;
    }

    /**
     * The application version this script should be ran on
     *
     * @return int
     */
    public function version(): int
    {
        return $this->version;
    }
}