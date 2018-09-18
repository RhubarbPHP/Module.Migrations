<?php

namespace Rhubarb\Modules\Migrations\Tests\Fixtures;

use Rhubarb\Crown\Tests\Fixtures\Modules\UnitTestingModule;
use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;
use Rhubarb\Modules\Migrations\MigrationsManager;
use Rhubarb\Modules\Migrations\MigrationsModule;
use Rhubarb\Modules\Migrations\MigrationsStateProvider;

class MigrationsTestCase extends RhubarbTestCase
{
    /** @var TestApplication $application */
    protected $application;
    /** @var MigrationsManager $manager */
    protected $manager;
    /** @var MigrationsStateProvider $provider */
    protected $provider;

    protected function setUp()
    {
        $parent = parent::setUp();

        $this->application = new TestApplication();
        $this->application->unitTesting = true;
        $this->application->context()->simulateNonCli = false;
        $this->application->registerModule(new UnitTestingModule());
        $this->application->registerModule(new MigrationsModule(TestMigrationsStateProvider::class));
        $this->application->initialiseModules();

        $this->manager = MigrationsManager::getMigrationsManager();
        $this->provider = MigrationsStateProvider::getProvider();
        /** @noinspection PhpUndefinedMethodInspection */
        $this->provider->reset();

        return $parent;
    }

    /**
     * @param int $version
     * @param int $priority
     * @param callable $execute
     * @return MigrationScriptInterface
     */
    protected function newScript($version = null, $execute = null)
    {
        $script = $this->createMock(MigrationScriptInterface::class);
        if (isset($version)) {
            $script->method('version')->willReturn($version);
        }
        if (isset($execute)) {
            $script->method('execute')->willReturnCallback($execute);
        }
        return $script;
    }

    protected function newScriptArray(...$versions): array
    {
        $return = [];
        foreach ($versions as $version) {
            $return[] = $this->newScript($version);
        }
        return $return;
    }
}
