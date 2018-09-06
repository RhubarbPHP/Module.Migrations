<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace Rhubarb\Modules\Migrations\Tests;

use PHPUnit\Framework\Error\Error;
use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;
use Rhubarb\Modules\Migrations\MigrationsManager;
use Rhubarb\Modules\Migrations\Tests\Fixtures\MigrationsTestCase;
use Rhubarb\Modules\Migrations\Tests\Fixtures\TestMigrationScript;
use Rhubarb\Modules\Migrations\Tests\Fixtures\TestMigrationsManager;
use Rhubarb\Modules\Migrations\UseCases\MigrationEntity;

class MigrationsManagerTest extends MigrationsTestCase
{
    /** @var MigrationsManager $manager */
    protected $manager;

    public function testGetMigrationScripts()
    {
        $this->manager->getMigrationScripts($entity = new MigrationEntity());
        verify($entity->migrationScripts)->isEmpty();

        $this->manager->registerMigrationScripts([new TestMigrationScript()]);
        $this->manager->getMigrationScripts($entity = new MigrationEntity());
        verify(count($entity->migrationScripts))->equals(1);

        $this->manager->registerMigrationScripts([
            new TestMigrationScript(),
            $this->createMock(MigrationScriptInterface::class),
            $this->createMock(MigrationScriptInterface::class),
            $this->createMock(MigrationScriptInterface::class),
            $this->createMock(MigrationScriptInterface::class),
        ]);
        $this->manager->getMigrationScripts($entity = new MigrationEntity());
        verify(count($entity->migrationScripts))->equals(5);

        foreach ($entity->migrationScripts as $script) {
            verify($script)->isInstanceOf(MigrationScriptInterface::class);
        }

        $this->manager->registerMigrationScripts(['LOLOLOLOL']);
        try {
            $this->manager->getMigrationScripts($entity = new MigrationEntity());
        } catch (Error $error) {
        }
    }

    public function testRegisterMigrationScripts()
    {
        $this->manager->setMigrationScriptsClasses(['end me']);
        $this->manager->registerMigrationScripts([]);
        verify($this->manager->getMigrationScripts())->isEmpty();
        $this->manager->registerMigrationScripts(['I', 'walk', 'this', 'lonely', 'road']);
        verify(count($this->manager->getMigrationScriptClasses()))->equals(5);

        $this->manager->registerMigrationScripts([TestMigrationScript::class]);
        verify(count($this->manager->getMigrationScriptClasses()))->equals(1);
        verify(count($this->manager->getMigrationScripts()))->equals(1);
    }
}
