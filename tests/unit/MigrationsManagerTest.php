<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace Rhubarb\Scaffolds\Migrations;

use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Scaffolds\Migrations\Scripts\MigrationScriptInterface;
use Rhubarb\Scaffolds\Migrations\Tests\Fixtures\MigrationsTestCase;
use Rhubarb\Scaffolds\Migrations\Tests\Fixtures\TestMigrationScript;
use Rhubarb\Scaffolds\Migrations\Tests\Fixtures\TestMigrationsManager;

class MigrationsManagerTest extends MigrationsTestCase
{
    /** @var TestMigrationsManager $manager */
    protected $manager;

    public function testGetMigrationScripts()
    {
        verify($this->manager->getMigrationScripts())->isEmpty();

        $this->manager->setMigrationScriptsClasses([TestMigrationScript::class]);
        verify(count($this->manager->getMigrationScripts()))->equals(1);

        $this->manager->setMigrationScriptsClasses([
            TestMigrationScript::class,
            get_class($this->createMock(MigrationScriptInterface::class)),
            get_class($this->createMock(MigrationScriptInterface::class)),
            get_class($this->createMock(MigrationScriptInterface::class)),
            get_class($this->createMock(MigrationScriptInterface::class)),
        ]);
        verify(count($scripts = $this->manager->getMigrationScripts()))->equals(5);

        foreach ($scripts as $script) {
            verify($script)->isInstanceOf(MigrationScriptInterface::class);
        }

        $this->manager->setMigrationScriptsClasses(['LOLOLOLOL']);
        $this->expectException(ImplementationException::class);
        $this->manager->getMigrationScripts();
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
