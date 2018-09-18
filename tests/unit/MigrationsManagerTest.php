<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace Rhubarb\Modules\Migrations\Tests;

use Error;
use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;
use Rhubarb\Modules\Migrations\MigrationsManager;
use Rhubarb\Modules\Migrations\Tests\Fixtures\MigrationsTestCase;
use Rhubarb\Modules\Migrations\Tests\Fixtures\TestMigrationScript;

class MigrationsManagerTest extends MigrationsTestCase
{
    /** @var MigrationsManager $manager */
    protected $manager;

    public function testMigrationScriptsReturned()
    {
        $this->manager->registerMigrationScripts($this->newScriptArray(1, 2, 3, 4));
        verify(is_array($this->manager->getMigrationScripts(0, 5)))->true();
        verify($this->manager->getMigrationScripts(0, 5))->count(4);
        foreach ($this->manager->getMigrationScripts(0, 5) as $script) {
            verify($script)->isInstanceOf(MigrationScriptInterface::class);
        }
    }

    public function testMigrationScriptsReturnedInRange()
    {
        $this->manager->registerMigrationScripts($this->newScriptArray(1, 2, 3, 4, 5, 6, 7, 7, 7, 7, 8, 9, 10));
        verify($this->manager->getMigrationScripts(5, 8))->count(7);
    }

    public function testMigrationScriptsReturnedSorted()
    {
        $this->manager->registerMigrationScripts($this->newScriptArray(9, 1, 2, 3, 8, 7, 4, 6, 5));
        $scripts = $this->manager->getMigrationScripts(0, 10);
        foreach (range(1, 9) as $version) {
            verify($scripts[$version - 1]->version())->equals($version);
        }
    }

    public function testSkipScriptsNotReturned()
    {
        $this->manager->registerMigrationScripts(
            array_merge($this->newScriptArray(1, 2, 3), [new TestMigrationScript(2)])
        );
        verify($this->manager->getMigrationScripts(0, 10))->count(4);
        verify($this->manager->getMigrationScripts(0, 10, [TestMigrationScript::class]))->count(3);
    }

    public function testRegisterMigrationScripts()
    {
        $this->manager->registerMigrationScripts([]);
        verify($this->manager->getMigrationScripts(-1000, 1000))->isEmpty();

        $this->manager->registerMigrationScripts($this->newScriptArray(3, 4, 5));
        verify($this->manager->getMigrationScripts(-1000, 1000))->count(3);
    }

    public function testRegisterInvalidMigrationScripts()
    {
        $this->manager->registerMigrationScripts(['fail', 'walk', 'this', 'lonely', 'road']);
        try {
            $this->manager->getMigrationScripts(0, 10);
            $this->fail('invalid scripts should stop execution');
        } catch (Error $error) {
            verify($error->getMessage())->contains('fail');
        }
    }

    public function testGetScriptClasses() {
        $this->manager->registerMigrationScripts($this->newScriptArray(1,2,3));
        verify($this->manager->getRegisteredMigrationScriptClasses())->count(3);
    }
}
