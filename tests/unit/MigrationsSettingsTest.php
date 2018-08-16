<?php

namespace Rhubarb\Scaffolds\Migrations;

use Rhubarb\Scaffolds\Migrations\Tests\Fixtures\MigrationsTestCase;
use Rhubarb\Scaffolds\Migrations\Tests\Fixtures\TestMigrationsManager;

class MigrationsSettingsTest extends MigrationsTestCase
{
    /** @var TestMigrationsManager $manger */
    protected $manager;
    /** @var MigrationsSettings $settings */
    protected $settings;

    public function testLocalVersion()
    {
        $this->settings->setLocalVersion(1);
        verify(file_exists($this->settings->getLocalVersionFilePath()))->true();
        verify(file_get_contents($this->settings->getLocalVersionFilePath()))->equals(1);

        $this->clearLocalVersion();
        verify(file_exists($this->settings->getLocalVersionFilePath()))->false();
        verify($this->settings->getLocalVersion())->equals(0);
        verify(file_exists($this->settings->getLocalVersionFilePath()))->true();
        verify(file_get_contents($this->settings->getLocalVersionFilePath()))->equals(0);
    }

    public function testResumeScript()
    {
        $this->clearResumeScript();
        $getResumeScriptFileContents = function () {
            if (file_exists($this->settings->getResumeScriptFilePath())) {
                return file_get_contents($this->settings->getResumeScriptFilePath());
            }
            return null;
        };
        verify($this->settings->getResumeScript())->isEmpty();

        $this->settings->setResumeScript('BLAAAAAH');
        verify($getResumeScriptFileContents())->equals('BLAAAAAH');

        $this->settings->setResumeScript('NOM');
        verify($getResumeScriptFileContents())->equals('NOM');


        $this->settings->setResumeScript('');
        verify($getResumeScriptFileContents())->isEmpty();

        $this->clearResumeScript();
        verify($getResumeScriptFileContents())->null();
        verify($this->settings->getResumeScript())->null();
    }

    public function testChangingFileLocation() {
        $this->settings->setLocalVersion(1);
        verify(file_get_contents($this->settings->getLocalVersionFilePath()))->equals(1);
        $oldLocPath = $this->settings->getLocalVersionFilePath();
        $this->settings->setLocalVersionPath(__DIR__ . '/../_data/locver.lock');
        verify(file_get_contents($this->settings->getLocalVersionFilePath()))->equals(1);

        $this->settings->setResumeScript('lads');
        verify(file_get_contents($this->settings->getResumeScriptFilePath()))->equals('lads');
        $this->settings->setResumeScriptPath(__DIR__ . '/../_data/resscr.lock');
        verify($this->settings->getResumeScriptFilePath())->notEquals($oldLocPath);
        verify(file_get_contents($this->settings->getResumeScriptFilePath()))->equals('lads');
    }

    private function clearLocalVersion()
    {
        $this->settings->localVersion = null;
        if (file_exists($this->settings->getLocalVersionFilePath())) {
            unlink($this->settings->getLocalVersionFilePath());
        }
    }

    private function clearResumeScript()
    {
        $this->settings->resumeScript = null;
        if (file_exists($this->settings->getResumeScriptFilePath())) {
            unlink($this->settings->getResumeScriptFilePath());
        }
    }
}
