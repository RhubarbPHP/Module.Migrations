<?php


namespace Rhubarb\Scaffolds\Migrations\Tests\Fixtures;


use Rhubarb\Crown\Tests\Fixtures\TestCases\RhubarbTestCase;
use Rhubarb\Scaffolds\Migrations\MigrationsManager;
use Rhubarb\Scaffolds\Migrations\MigrationsModule;
use Rhubarb\Scaffolds\Migrations\MigrationsSettings;
use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\Offline\Offline;
use Rhubarb\Stem\Repositories\Repository;
use Rhubarb\Stem\Schema\SolutionSchema;

class MigrationsTestCase extends RhubarbTestCase
{
    protected $manager;

    protected $settings;

    protected function setUp()
    {
        $parent = parent::setUp();

        $this->application->registerModule(new MigrationsModule());
        $this->application->initialiseModules();

        Repository::setDefaultRepositoryClassName(Offline::class);
        Model::deleteRepositories();
        SolutionSchema::registerSchema("Schema", MigrationsTestSchema::class);

        MigrationsManager::registerMigrationManager(TestMigrationsManager::class);
        $this->manager = MigrationsManager::getMigrationsManager();
        $this->settings = MigrationsSettings::singleton();

        $this->settings->pageSize = 100;
        $this->settings->repositoryType = Offline::class;

        return $parent;
    }
}