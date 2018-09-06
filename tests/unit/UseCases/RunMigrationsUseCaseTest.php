<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace Rhubarb\Modules\Migrations\Tests\UseCases;

use Rhubarb\Crown\LoginProviders\Exceptions\LoginFailedException;
use Rhubarb\Modules\Migrations\MigrationsManager;
use Rhubarb\Modules\Migrations\MigrationsStateProvider;
use Rhubarb\Modules\Migrations\Tests\Fixtures\MigrationsTestCase;
use Rhubarb\Modules\Migrations\Tests\Fixtures\TestMigrationScript;
use Rhubarb\Modules\Migrations\UseCases\MigrationEntity;
use Rhubarb\Modules\Migrations\UseCases\RunMigrationsUseCase;

class RunMigrationsUseCaseTest extends MigrationsTestCase
{
    /** @var MigrationsManager $manager */
    protected $manager;
    /** @var MigrationsStateProvider $stateProvider */
    protected $stateProvider;

    public function testLocalVersionIncreases()
    {
        $this->stateProvider->setLocalVersion(1);
        $this->manager->registerMigrationScripts([$this->newScript(6)]);
        RunMigrationsUseCase::execute($this->makeEntity(7));
        verify($this->stateProvider->getLocalVersion())->equals(7);

        $this->stateProvider->setLocalVersion(1);
        RunMigrationsUseCase::execute($this->makeEntity(2));
        verify($this->stateProvider->getLocalVersion())->equals(2);

        // Version doesn't increase on errors.
        try {
            $entity = $this->makeEntity(3);
            $entity->migrationScripts[] = $this->newScript(2, 1, function () {
                throw new \Error('test');
            });
            RunMigrationsUseCase::execute($entity);
            $this->fail('Execution should have halted when the error was thrown');
        } catch (\Error $error) {
        } finally {
            verify($this->stateProvider->getLocalVersion())->equals(2);
        }
    }

    public function testMigrationScriptsRetrieved()
    {
        $this->stateProvider->setLocalVersion(77);

        $entity = new MigrationEntity();
        $entity->startVersion = 1;
        $entity->endVersion = 1000;

        $this->manager->getMigrationScripts($entity = $this->makeEntity());
        verify($entity->migrationScripts)->isEmpty();

        foreach ([78, 80, 80, 81] as $version) {
            $migrationScripts[] = $this->newScript($version);
        }
        $this->manager->registerMigrationScripts($migrationScripts);
        $entity->startVersion = 79;
        $entity->endVersion = 80;
        $this->manager->getMigrationScripts($entity);
        verify(count($entity->migrationScripts))->equals(2);

        $migrationScripts = [];
        $loop = 0;
        foreach ([89, 72, 80, 79, 80, 81] as $version) {
            $migrationScripts[] = $this->newScript($version, $loop++);
        }
        $this->manager->registerMigrationScripts($migrationScripts);
        $this->manager->getMigrationScripts($entity = $this->makeEntity(80, 79));
        verify(count($entity->migrationScripts))->equals(3);
        verify($entity->migrationScripts[0]->version())->equals(79);
        verify($entity->migrationScripts[1]->version())->equals(80);
        verify($entity->migrationScripts[1]->priority())->greaterThan($entity->migrationScripts[2]->priority());
        verify($entity->migrationScripts[2]->version())->equals(80);

        $setUpScripts = function () use (&$loop) {
            $this->stateProvider->setLocalVersion(4);
            $migrationScripts = [];
            foreach ([5, 5, 6, 7, 7, 7, 8] as $version) {
                if ($version == 6) {
                    $migrationScripts[] = new TestMigrationScript();
                } else {
                    $migrationScripts[] = $this->newScript($version, $loop++);
                }
            }
            $this->manager->registerMigrationScripts($migrationScripts);
        };

        $setUpScripts();
        $this->stateProvider->storeResumePoint(new TestMigrationScript());
        $entity = $this->makeEntity(9, 1, TestMigrationScript::class);
        $entity->resume = true;
        RunMigrationsUseCase::execute($entity);
        verify(count($migrationScripts))->equals(5);

        $setUpScripts();
        $entity = new MigrationEntity();
        $entity->endVersion = 9;
        $entity->skipScripts = [TestMigrationScript::class];
        foreach ($migrationScripts as $migrationScript) {
            verify($migrationScript)->isNotInstanceOf(TestMigrationScript::class);
        }
    }

    public function testScriptsRunInRange()
    {
        $this->stateProvider->setLocalVersion(1);
        $migrationScripts = [];
        $scriptsRan = $loop = 0;
        foreach ([0, 1, 0, 2, 1, 4] as $version) {
            $migrationScripts[] = $this->newScript($version, $loop++, function () use (&$scriptsRan) {
                return $scriptsRan++;
            });
        }
        $this->manager->registerMigrationScripts($migrationScripts);
        RunMigrationsUseCase::execute($this->makeEntity(2));
        verify($scriptsRan)->equals(3);
    }


    public function testSkipScripts()
    {
        foreach (range(1, 6) as $number) {
            $scripts[] = $this->newScript($number);
        }
        $failScript = new TestMigrationScript();
        $failScript->execute = function () {
            $this->fail('This script should not be run!');
        };
        $scripts[3] = $failScript;

        $this->stateProvider->setLocalVersion(1);
        $this->manager->registerMigrationScripts($scripts);
        $entity = $this->makeEntity(7, 1);
        $entity->skipScripts[] = TestMigrationScript::class;
        RunMigrationsUseCase::execute($entity);
        // This script will fail if the failScript runs.
    }

    public function testExecutionStopsOnError()
    {
        $this->manager->registerMigrationScripts(
            [
                $this->newScript(1),
                $this->newScript(2),
                $this->newScript(3),
                $script = $this->newScript(4, 1, function () {
                    throw new \Error("Error Thrown");
                }),
                $this->newScript(5, 1, function () {
                    throw new LoginFailedException("I break things");
                })
            ]
        );

        try {
            RunMigrationsUseCase::execute($this->makeEntity(6, 1));
            $this->fail("Failed to stop migration on error");
        } catch (\Error $error) {
            verify($error->getMessage())->equals("Error Thrown");
        }
    }
}
