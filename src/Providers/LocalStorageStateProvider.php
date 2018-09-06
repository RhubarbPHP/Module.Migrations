<?php

namespace Rhubarb\Modules\Migrations\Providers;

use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;
use Rhubarb\Modules\Migrations\MigrationsStateProvider;
use Rhubarb\Modules\Migrations\UseCases\MigrationEntity;

class LocalStorageStateProvider extends MigrationsStateProvider
{
    const
        DEFAULT_LOCAL_VERSION_FILE = 'local-version.lock',
        DEFAULT_RESUME_SCRIPT_FILE = 'resume-script.lock';

    /** @var string $localVersionPath */
    public $localVersionPath;
    /** @var string $resumeScriptPath */
    public $resumeScriptPath;

    protected function initialiseDefaultValues()
    {
        $this->setLocalVersionPath(sys_get_temp_dir() . '/' . self::DEFAULT_LOCAL_VERSION_FILE);
        $this->setResumeScriptPath(sys_get_temp_dir() . '/' . self::DEFAULT_RESUME_SCRIPT_FILE);
    }

    /**
     * @param MigrationEntity $entity
     */
    public function applyResumePoint(MigrationEntity $entity): void
    {
        $resumeScript = $this->getResumeScript();
        if ($resumeScript) {
            /** @var MigrationScriptInterface $resumeScript */
            $entity->startVersion = $resumeScript->version();
            $entity->startPriority = $resumeScript->priority();
        }
    }

    /**
     * @return int
     */
    public function getLocalVersion(): int
    {
        return (int)file_get_contents($this->getLocalVersionFilePath());
    }

    /**
     * @param int $newLocalVersion
     * @throws ImplementationException
     */
    public function setLocalVersion(int $newLocalVersion): void
    {
        file_put_contents($this->getLocalVersionFilePath(), $newLocalVersion);
    }

    /**
     * @return string
     */
    public function getLocalVersionFilePath(): string
    {
        return $this->localVersionPath ?? sys_get_temp_dir() . '/localversion.txt';
    }

    /**
     * @return string
     */
    public function getResumeScript(): string
    {
        return file_get_contents($this->getResumeScriptFilePath());
    }

    /**
     * @param string $resumeScript
     */
    public function setResumeScript(string $resumeScript = null): void
    {
        if (is_null($resumeScript)) {
            unlink($this->getResumeScriptFilePath());
        } else {
            file_put_contents($this->getResumeScriptFilePath(), $resumeScript);
        }
    }

    /**
     * @return string
     */
    public function getResumeScriptFilePath(): string
    {
        return $this->resumeScriptPath ?? sys_get_temp_dir() . '/resumescript.txt';
    }

    /**
     * @param string $localVersionPath
     */
    public function setLocalVersionPath(string $localVersionPath): void
    {
        $this->moveLocalFile($this->getLocalVersionFilePath(), $localVersionPath);
    }

    /**
     * @param string $resumeScriptPath
     */
    public function setResumeScriptPath(string $resumeScriptPath): void
    {
        $this->moveLocalFile($this->getResumeScriptFilePath(), $resumeScriptPath);
    }

    /**
     * @param $oldPath
     * @param $newPath
     */
    private function moveLocalFile($oldPath, $newPath)
    {
        file_put_contents($newPath, file_get_contents($oldPath) ?: '');
        unlink($oldPath);
    }
}