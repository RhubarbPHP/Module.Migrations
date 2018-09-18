<?php

namespace Rhubarb\Modules\Migrations\Tests\Fixtures;

use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;
use Rhubarb\Modules\Migrations\MigrationsStateProvider;

class TestMigrationsStateProvider extends MigrationsStateProvider
{
    /**
     * @return int
     */
    public function getLocalVersion(): int
    {
        return is_readable($this->versionPath()) ? file_get_contents($this->versionPath()) : 0;
    }

    /**
     * @param int $newLocalVersion
     */
    public function setLocalVersion(int $newLocalVersion): void
    {
        file_put_contents($this->versionPath(), $newLocalVersion);
    }

    public function markScriptCompleted(MigrationScriptInterface $migrationScript): void
    {
        file_put_contents(
            $this->scriptsPath(),
            json_encode(
                array_merge(
                    $this->getCompletedScripts(),
                    [get_class($migrationScript)]
                )
            )
        );
    }

    public function isScriptComplete(string $className): bool
    {
        return array_search($className, $this->getCompletedScripts()) !== false;
    }

    public function getCompletedScripts(): array
    {

        return is_readable($this->scriptsPath()) ? json_decode(file_get_contents($this->scriptsPath())) : [];
    }

    private function scriptsPath(): string
    {
        return sys_get_temp_dir() . '/completescripts.txt';
    }

    private function versionPath(): string
    {
        return sys_get_temp_dir() . '/localversion.txt';
    }

    public function reset()
    {
        $paths = [
            sys_get_temp_dir() . '/completescripts.txt',
            sys_get_temp_dir() . '/localversion.txt',
        ];

        foreach ($paths as $path) {
            if (is_readable($path)) {
                unlink($path);
            }
        }
    }
}