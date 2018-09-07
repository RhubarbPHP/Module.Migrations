<?php

namespace Rhubarb\Modules\Migrations\Tests;

use Rhubarb\Modules\Migrations\MigrationsManager;
use Rhubarb\Modules\Migrations\Providers\LocalStorageStateProvider;
use Rhubarb\Modules\Migrations\Tests\Fixtures\MigrationsTestCase;

class MigrationsStateProviderTest extends MigrationsTestCase
{
    /** @var MigrationsManager $manger */
    protected $manager;
    /** @var LocalStorageStateProvider $stateProvider */
    protected $stateProvider;

    public function testLocalVersion()
    {
        $this->stateProvider->setLocalVersion(1);
        verify(file_exists($this->stateProvider->getLocalVersionFilePath()))->true();
        verify(file_get_contents($this->stateProvider->getLocalVersionFilePath()))->equals(1);

        $this->clearLocalVersion();
        verify(file_exists($this->stateProvider->getLocalVersionFilePath()))->false();
        verify($this->stateProvider->getLocalVersion())->equals(0);
    }

    public function testResumeScript()
    {
        $this->clearResumeScript();
        $getResumeScriptFileContents = function () {
            if (file_exists($this->stateProvider->getResumeScriptFilePath())) {
                return file_get_contents($this->stateProvider->getResumeScriptFilePath());
            }
            return null;
        };
        verify($this->stateProvider->getResumeScript())->isEmpty();

        $this->stateProvider->setResumeScript('BLAAAAAH');
        verify($getResumeScriptFileContents())->equals('BLAAAAAH');

        $this->stateProvider->setResumeScript('NOM');
        verify($getResumeScriptFileContents())->equals('NOM');


        $this->stateProvider->setResumeScript('');
        verify($getResumeScriptFileContents())->isEmpty();

        $this->clearResumeScript();
        verify($getResumeScriptFileContents())->null();
        verify($this->stateProvider->getResumeScript())->isEmpty();
    }

    public function testChangingFileLocation()
    {
        $this->stateProvider->setLocalVersion(1);
        verify(file_get_contents($this->stateProvider->getLocalVersionFilePath()))->equals(1);
        $oldLocPath = $this->stateProvider->getLocalVersionFilePath();
        $this->stateProvider->setLocalVersionPath(__DIR__ . '/../_data/locver.lock');
        verify(file_get_contents($this->stateProvider->getLocalVersionFilePath()))->equals(1);

        $this->stateProvider->setResumeScript('lads');
        verify(file_get_contents($this->stateProvider->getResumeScriptFilePath()))->equals('lads');
        $this->stateProvider->setResumeScriptPath(__DIR__ . '/../_data/resscr.lock');
        verify($this->stateProvider->getResumeScriptFilePath())->notEquals($oldLocPath);
        verify(file_get_contents($this->stateProvider->getResumeScriptFilePath()))->equals('lads');
    }

    private function clearLocalVersion()
    {
        if (file_exists($this->stateProvider->getLocalVersionFilePath())) {
            unlink($this->stateProvider->getLocalVersionFilePath());
        }
    }

    private function clearResumeScript()
    {
        $this->stateProvider->resumeScript = null;
        if (file_exists($this->stateProvider->getResumeScriptFilePath())) {
            unlink($this->stateProvider->getResumeScriptFilePath());
        }
    }
}
