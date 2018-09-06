<?php

namespace Rhubarb\Modules\Migrations\Tests\Fixtures;

use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Modules\Migrations\MigrationsManager;
use Rhubarb\Modules\Migrations\MigrationsModule;
use Rhubarb\Modules\Migrations\MigrationsStateProvider;
use Rhubarb\Modules\Migrations\Providers\LocalStorageStateProvider;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\Offline\Offline;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\SolutionSchema;

class MigrationsTestCase extends RhubarbTestCase
{
    /** @var TestMigrationsManager $manager */
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
}