<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace Rhubarb\Modules\Migrations\Tests;

use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;
use Rhubarb\Modules\Migrations\Tests\Fixtures\MigrationsTestCase;
use Rhubarb\Modules\Migrations\Tests\Fixtures\TestMigrationScript;
use Rhubarb\Modules\Migrations\Tests\Fixtures\TestMigrationsManager;
use Rhubarb\Modules\Migrations\UseCases\MigrationEntity;

class MigrationsManagerTest extends MigrationsTestCase
{
    /** @var TestMigrationsManager $manager */
    protected $manager;

    public function testGetMigrationScripts()
    {
        verify($this->manager->getMigrationScripts(new MigrationEntity()))->isEmpty();

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
