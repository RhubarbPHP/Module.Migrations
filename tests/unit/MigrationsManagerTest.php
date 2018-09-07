<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace Rhubarb\Modules\Migrations\Tests;

use Error;
use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;
use Rhubarb\Modules\Migrations\MigrationsManager;
use Rhubarb\Modules\Migrations\Tests\Fixtures\MigrationsTestCase;
use Rhubarb\Modules\Migrations\Tests\Fixtures\TestMigrationScript;
use Rhubarb\Modules\Migrations\Tests\Fixtures\TestMigrationsManager;
use Rhubarb\Modules\Migrations\UseCases\MigrationEntity;
use Rhubarb\Modules\Migrations\UseCases\RunMigrationsUseCase;

class MigrationsManagerTest extends MigrationsTestCase
{
    /** @var MigrationsManager $manager */
    protected $manager;

    public function testGetMigrationScripts()
    {
        $this->manager->getMigrationScripts($entity = new MigrationEntity());
        verify($entity->migrationScripts)->isEmpty();

        $this->manager->registerMigrationScripts([new TestMigrationScript()]);
        $this->manager->getMigrationScripts($entity = $this->makeEntity(100, 0));
        verify(count($entity->migrationScripts))->equals(1);

        $this->manager->registerMigrationScripts([
            new TestMigrationScript(),
            $this->newScript(7),
            $this->newScript(8),
            $this->newScript(8),
            $this->newScript(9),
        ]);
        RunMigrationsUseCase::execute($entity = $this->makeEntity(10,1));
        verify(count($entity->migrationScripts))->equals(5);

        foreach ($entity->migrationScripts as $script) {
            verify($script)->isInstanceOf(MigrationScriptInterface::class);
        }

        $this->manager->registerMigrationScripts(['LOLOLOLOL']);
        try {
            $this->manager->getMigrationScripts($entity = new MigrationEntity());
            $this->fail('registered scripts should be objects not strings');
        } catch (Error $error) {
        }
    }

    public function testRegisterMigrationScripts()
    {
        $this->manager->registerMigrationScripts([]);
        $this->manager->getMigrationScripts($entity = $this->makeEntity(100, 0));
        verify($entity->migrationScripts)->isEmpty();

        $this->manager->registerMigrationScripts([$this->newScript(), $this->newScript(), $this->newScript()]);
        $this->manager->getMigrationScripts($entity = $this->makeEntity(100, 0));
        verify(count($entity->migrationScripts))->equals(3);
    }
}
