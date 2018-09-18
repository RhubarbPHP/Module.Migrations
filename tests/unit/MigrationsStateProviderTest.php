<?php

namespace Rhubarb\Modules\Migrations\Tests;

use Rhubarb\Modules\Migrations\MigrationsManager;
use Rhubarb\Modules\Migrations\Tests\Fixtures\MigrationsTestCase;
use Rhubarb\Modules\Migrations\Tests\Fixtures\TestMigrationScript;
use Rhubarb\Modules\Migrations\Tests\Fixtures\TestMigrationsStateProvider;

class MigrationsStateProviderTest extends MigrationsTestCase
{
    /** @var MigrationsManager $manger */
    protected $manager;
    /** @var TestMigrationsStateProvider $provider */
    protected $provider;

    protected function setUp()
    {
        parent::setUp();
        $this->provider::setProviderClassName(TestMigrationsStateProvider::class);
        $this->provider->reset();
    }

    public function testLocalVersion()
    {
        $this->provider->setLocalVersion(92);
        verify($this->provider->getLocalVersion())->equals(92);
        $this->provider->setLocalVersion(78);
        verify($this->provider->getLocalVersion())->equals(78);
    }

    public function testScriptCompleted()
    {
        verify($this->provider->isScriptComplete(TestMigrationScript::class))->false();
        $this->provider->markScriptCompleted(new TestMigrationScript(2));
        verify($this->provider->isScriptComplete(TestMigrationScript::class))->true();
    }

    public function testGetCompletedScripts()
    {
        verify($this->provider->getCompletedScripts())->isEmpty();
        $this->provider->markScriptCompleted(new TestMigrationScript(2));
        verify($this->provider->getCompletedScripts())->count(1);
        verify($this->provider->getCompletedScripts()[0])->equals(TestMigrationScript::class);
    }

    public function testGetApplicationVersion()
    {
        $this->application->setVersion(56);
        verify($this->provider::getApplicationVersion())->equals(56);
    }
}
