<?php

namespace Rhubarb\Scaffolds\Migrations;

use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Crown\Settings;
use Rhubarb\Stem\Repositories\MySql\MySql;

class MigrationsSettings extends Settings
{
    const
        DEFAULT_LOCAL_VERSION_FILE = 'local-version.lock',
        DEFAULT_RESUME_SCRIPT_FILE = 'resume-script.lock';

    /** @var int $localVersion */
    public $localVersion;
    /** @var string $localVersionPath */
    public $localVersionPath;
    /** @var string $resumeScript */
    public $resumeScript;
    /** @var string $resumeScriptPath */
    public $resumeScriptPath;
    /**
     * Used when updating values in a table.
     *
     * @var int $pageSize
     */
    public $pageSize = 100;

    public $repositoryType = MySql::class;

    protected function initialiseDefaultValues()
    {
        $this->localVersionPath = sys_get_temp_dir() . '/' . self::DEFAULT_LOCAL_VERSION_FILE;
        $this->resumeScriptPath = sys_get_temp_dir() . '/' . self::DEFAULT_RESUME_SCRIPT_FILE;
    }

    /**
     * @return int
     */
    public function getLocalVersion(): int
    {
        if ($this->localVersion) {
            return $this->localVersion;
        } else {
            if (file_exists($this->getLocalVersionFilePath())) {
                $this->localVersion = (int)file_get_contents($this->getLocalVersionFilePath());
                return $this->localVersion;
            } else {
                $this->localVersion = 0; // ASSUME NOTHING
                file_put_contents($this->getLocalVersionFilePath(), $this->localVersion);
                return $this->localVersion;
            }
        }
    }

    /**
     * @param int $newLocalVersion
     * @throws ImplementationException
     */
    public function setLocalVersion(int $newLocalVersion): void
    {
        if ($this->getLocalVersionFilePath()) {
            file_put_contents($this->getLocalVersionFilePath(), $newLocalVersion);
            $this->localVersion = $newLocalVersion;
        } else {
            throw new ImplementationException('No path provided for Local Version in Migration Settings!');
        }
    }

    /**
     * @return string
     */
    public function getLocalVersionFilePath(): string
    {
        return $this->localVersionPath;
    }

    /**
     * @return null|string
     */
    public function getResumeScript()
    {
        if ($this->resumeScript) {
            return $this->resumeScript;
        } else {
            if (file_exists($this->getResumeScriptFilePath())) {
                $this->resumeScript = file_get_contents($this->getResumeScriptFilePath());
                return $this->resumeScript;
            } else {
                return null;
            }
        }
    }

    /**
     * @param string $resumeScript
     */
    public function setResumeScript(string $resumeScript): void
    {
        file_put_contents($this->getResumeScriptFilePath(), $resumeScript);
        $this->resumeScript = $resumeScript;
    }

    /**
     * @return string
     */
    public function getResumeScriptFilePath(): string
    {
        return $this->resumeScriptPath;
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

    private function moveLocalFile($oldPath, $newPath)
    {
        if (file_get_contents($this->localVersionPath) !== false) {
            $localValue = file_get_contents($oldPath);
            unlink($oldPath);
            file_put_contents($newPath, $localValue);
        }


    }

}