<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace Rhubarb\Modules\Migrations\Tests\UseCases;

use Rhubarb\Crown\LoginProviders\Exceptions\LoginFailedException;
use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;
use Rhubarb\Modules\Migrations\MigrationsManager;
use Rhubarb\Modules\Migrations\MigrationsStateProvider;
use Rhubarb\Modules\Migrations\Tests\Fixtures\MigrationsTestCase;
use Rhubarb\Modules\Migrations\Tests\Fixtures\TestMigrationScript;

class RunMigrationsUseCaseTest extends MigrationsTestCase
{
    /** @var MigrationsManager $manager */
    protected $manager;
    /** @var MigrationsStateProvider $provider */
    protected $provider;

    public function testLocalVersionUpdates()
    {
        $this->provider->runMigrations($this->newEntity(1, 7));
        verify($this->provider->getLocalVersion())->equals(7);

        $this->manager->registerMigrationScripts([$this->newScript(5)]);
        $this->provider->runMigrations($this->newEntity(1, 7));
        verify($this->provider->getLocalVersion())->equals(7);
    }

    public function testLocalVersionUpdatesWithErrors()
    {
        try {
            $entity = $this->newEntity(1, 4);
            $this->manager->registerMigrationScripts(
                [
                    $this->newScript(3, function () {
                        throw new \Error('test');
                    }),
                    $this->newScript(2)
                ]
            );
            $this->provider->runMigrations($entity);
            $this->fail('Execution should have halted when the error was thrown');
        } catch (\Error $error) {
        } finally {
            verify($this->provider->getLocalVersion())->equals(2);
        }
    }


    public function testMigrationScriptsRetrieved()
    {
        $executedScripts = 0;
        foreach ([3, 3, 4, 5] as $version) {
            $migrationScripts[] = $this->newScript($version, function () use (&$executedScripts) {
                return $executedScripts++;
            });
        }
        $this->manager->registerMigrationScripts($migrationScripts);
        $this->provider->runMigrations($this->newEntity(1, 6));
        verify($executedScripts)->equals(4);
    }

    public function testRetrievedScriptsAreInRange()
    {
        $this->provider->setLocalVersion(1);
        $this->application->setVersion(10);

        // Empty array returned when no migration scripts registered
        verify($this->manager->getMigrationScripts(1, 10))->isEmpty();

        // Empty array when no scripts within versions range is registered
        $this->manager->registerMigrationScripts($this->newScriptArray(99, 0, 11));
        verify($this->manager->getMigrationScripts(1, 10))->isEmpty();

        // Only scripts within range version should be returned
        $this->manager->registerMigrationScripts($this->newScriptArray(0, 1, 5, 10, 11));
        verify($this->manager->getMigrationScripts(1, 10))->count(3);
    }

    public function testReturnedScriptsAreObjects()
    {
        $this->provider->setLocalVersion(1);
        $this->application->setVersion(10);
        $this->manager->registerMigrationScripts(
            [
                new TestMigrationScript(rand(1, 10)),
                $this->newScript(rand(1, 10)),
                $this->newScript(rand(1, 10)),
                $this->newScript(rand(1, 10)),
            ]
        );
        foreach ($this->manager->getMigrationScripts(1, 10) as $script) {
            verify($script)->isInstanceOf(MigrationScriptInterface::class);
        }
    }

    public function testSkipScriptsDoNotRun()
    {
        $entity = $this->newEntity(1, 10);
        $entity->skipScripts[] = TestMigrationScript::class;
        $this->manager->registerMigrationScripts(
            array_merge(
                [
                    new TestMigrationScript(2, function () {
                        print 'anotha one';
                        throw new \Error('skipped failing script should not run');
                    })
                ],
                $this->newScriptArray()
            )
        );
        $this->provider->runMigrations($entity);
        // Failing script was skipped
        verify($this->provider->getLocalVersion())->equals(10);
    }

    public function testScriptsRunInOrder()
    {
        $migrationScripts = [];
        $currentVersion = $runningTotal = 0;
        foreach (range(1, 6) as $version) {
            $migrationScripts[] = $this->newScript($version, function () use (&$runningTotal, &$currentVersion) {
                $currentVersion++;
                // Count the previous script versions to ensure the current is the next in order.
                if ($runningTotal !== array_sum(range(0, $currentVersion - 1))) {
                    throw new \Error('scripts did not run in order');
                }
                $runningTotal += $currentVersion;
            });
        }
        $this->manager->registerMigrationScripts($migrationScripts);
        $this->provider->runMigrations($this->newEntity(1, 6));
    }

    public function testExecutionStopsOnError()
    {
        $this->manager->registerMigrationScripts(
            array_merge(
                $this->newScriptArray(1, 2, 3),
                [
                    $script = $this->newScript(4, function () {
                        throw new \Error("Error Thrown");
                    }),
                    $this->newScript(5, function () {
                        throw new LoginFailedException("I break things");
                    })
                ]
            )
        );

        try {
            $this->provider->runMigrations($this->newEntity(1, 6));
            $this->fail("Failed to stop migration on error");
        } catch (\Error $error) {
            verify($error->getMessage())->equals("Error Thrown");
        }
    }

    public function testScriptsMarkedComplete()
    {
        $this->manager->registerMigrationScripts([new TestMigrationScript(2)]);
        $this->provider->runMigrations($this->newEntity(1, 5));
        verify($this->provider->getCompletedScripts())->count(1);
        verify($this->provider->getCompletedScripts()[0])->equals(TestMigrationScript::class);
    }

    public function testRetryMigrations()
    {
        $runs = 0;
        $fail = 1;
        $this->manager->registerMigrationScripts(
            array_merge(
                $this->newScriptArray(2, 3, 3, 4, 5),
                [
                    $this->newScript(5, function () use (&$runs) {
                        $runs++;
                        if ($runs > 1) {
                            throw new \Error('This script should not be run more than once');
                        }
                    })
                ],
                [
                    new TestMigrationScript(5, function () use (&$fail) {
                        if ($fail--) {
                            throw new \Error('Fails the first time it is ran.');
                        }
                    })
                ],
                $this->newScriptArray(5, 6, 7)
            )
        );

        try {
            $this->provider->runMigrations($entity = $this->newEntity(1, 10));
            $this->fail('execution should have stopped when an error was thrown.');
        } catch (\Error $error) {
            verify($error->getMessage())->equals('Fails the first time it is ran.');
            verify($this->provider->getLocalVersion())->equals(5);
            $this->provider->runMigrations($entity);
            verify($this->provider->getLocalVersion())->equals(10);
        }
    }
}
