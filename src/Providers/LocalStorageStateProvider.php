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
        $resumeScriptClass = $this->getResumeScript();
        if ($resumeScriptClass) {
            /** @var MigrationScriptInterface $resumeScript */
            $resumeScript = new $resumeScriptClass();
            /** @var MigrationScriptInterface $resumeScriptClass */
            $entity->startVersion = $resumeScript->version();
            $entity->startPriority = $resumeScript->priority();
        }
    }

    public function storeResumePoint(MigrationScriptInterface $failingScript)
    {
        $this->setResumeScript(get_class($failingScript));
    }

    /**
     * @return int
     */
    public function getLocalVersion(): int
    {
        if (is_readable($this->getLocalVersionFilePath())) {
            return (int)file_get_contents($this->getLocalVersionFilePath());
        }
        return 0;
    }

    /**
     * @param int $newLocalVersion
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
        if (is_readable($this->getResumeScriptFilePath())) {
            return file_get_contents($this->getResumeScriptFilePath()) ?? '';
        }
        return '';
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
        $this->localVersionPath = $localVersionPath;
    }

    /**
     * @param string $resumeScriptPath
     */
    public function setResumeScriptPath(string $resumeScriptPath): void
    {
        $this->moveLocalFile($this->getResumeScriptFilePath(), $resumeScriptPath);
        $this->resumeScriptPath = $resumeScriptPath;
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