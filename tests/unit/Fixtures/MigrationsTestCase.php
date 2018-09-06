<?php

namespace Rhubarb\Modules\Migrations\Tests\Fixtures;

use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;
use Rhubarb\Modules\Migrations\MigrationsManager;
use Rhubarb\Modules\Migrations\MigrationsModule;
use Rhubarb\Modules\Migrations\MigrationsStateProvider;
use Rhubarb\Modules\Migrations\Providers\LocalStorageStateProvider;
use Rhubarb\Modules\Migrations\UseCases\MigrationEntity;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\Offline\Offline;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\SolutionSchema;

class MigrationsTestCase extends RhubarbTestCase
{
    /** @var MigrationsManager $manager */
    protected $manager;
    /** @var LocalStorageStateProvider $stateProvider */
    protected $stateProvider;

    protected function setUp()
    {
        $parent = parent::setUp();

        $this->application->registerModule(new MigrationsModule());
        $this->application->initialiseModules();

        Repository::setDefaultRepositoryClassName(Offline::class);
        Model::deleteRepositories();
        SolutionSchema::registerSchema("Schema", MigrationsTestSchema::class);

        $this->manager = MigrationsManager::getMigrationsManager();
        MigrationsStateProvider::setProviderClassName(LocalStorageStateProvider::class);
        $this->stateProvider = MigrationsStateProvider::getProvider();

        return $parent;
    }

    /**
     * @param int      $version
     * @param int      $priority
     * @param callable $execute
     * @return \PHPUnit\Framework\MockObject\MockObject
     */
    protected function newScript($version = null, $priority = null, $execute = null)
    {
        $script = $this->createMock(MigrationScriptInterface::class);
        if (isset($version)) {
            $script->method('version')->willReturn($version);

        }
        if (isset($priority)) {
            $script->method('priority')->willReturn($priority);
        }
        if (isset($execute)) {
            $script->method('execute')->willReturnCallback($execute);
        }
        return $script;
    }

    /**
     * @param int    $startVersion
     * @param int    $endVersion
     * @param string $resumeScript
     * @return MigrationEntity
     */
    protected function makeEntity($endVersion = null, $startVersion = null, $resumeScript = null)
    {
        $entity = new MigrationEntity();
        if (isset($startVersion)) {
            $entity->startVersion = $startVersion;
        }
        $entity->endVersion = $endVersion;
        $entity->resumeScript = $resumeScript;
        return $entity;
    }
}