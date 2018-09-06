<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace Rhubarb\Modules\Migrations\Tests\UseCases;

use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginFailedException;
use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;
use Rhubarb\Modules\Migrations\MigrationsManager;
use Rhubarb\Modules\Migrations\MigrationsSettings;
use Rhubarb\Modules\Migrations\MigrationsStateProvider;
use Rhubarb\Modules\Migrations\Tests\Fixtures\MigrationsTestCase;
use Rhubarb\Modules\Migrations\Tests\Fixtures\TestMigrationScript;
use Rhubarb\Modules\Migrations\Tests\Fixtures\TestMigrationsManager;
use Rhubarb\Modules\Migrations\UseCases\MigrationEntity;
use Rhubarb\Modules\Migrations\UseCases\RunMigrationsUseCase;

class MigrateUseCaseTest extends MigrationsTestCase
{
    /** @var MigrationsManager $manager */
    protected $manager;
    /** @var MigrationsStateProvider $stateProvider */
    protected $stateProvider;

    public function testLocalVersionIncreases()
    {
        $this->stateProvider->setLocalVersion(1);
        $this->manager->setMigrationScripts([$this->newScript(6)]);
        RunMigrationsUseCase::execute($this->makeEntity(7));
        verify($this->stateProvider->getLocalVersion())->equals(7);
    }

    public function testMigrationScriptsRetrieved()
    {
        $this->stateProvider->setLocalVersion(77);

        $entity = new MigrationEntity();
        $entity->startVersion = 1;
        $entity->endVersion = 1000;

        /** @var MigrationScriptInterface[] $migrationScripts */
        $migrationScripts = self::runMethodAsPublic('getMigrationScripts', $entity);
        verify($migrationScripts)->isEmpty();

        foreach ([78, 80, 80, 81] as $version) {
            $migrationScripts[] = $this->newScript($version);
        }
        $this->manager->setMigrationScripts($migrationScripts);
        $entity->startVersion = 79;
        $entity->endVersion = 80;
        $migrationScripts = self::runMethodAsPublic('getMigrationScripts', $entity);
        verify(count($migrationScripts))->equals(2);

        $migrationScripts = [];
        $loop = 0;
        foreach ([89, 72, 80, 79, 80, 81] as $version) {
            $migrationScripts[] = $this->newScript($version, $loop++);
        }
        $this->manager->setMigrationScripts($migrationScripts);
        $migrationScripts = self::runMethodAsPublic('getMigrationScripts', $entity);
        verify(count($migrationScripts))->equals(3);
        verify($migrationScripts[0]::version())->equals(79);
        verify($migrationScripts[1]::version())->equals(80);
        verify($migrationScripts[1]::priority())->greaterThan($migrationScripts[2]::priority());
        verify($migrationScripts[2]::version())->equals(80);

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
            $this->manager->setMigrationScripts($migrationScripts);
        };

        $setUpScripts();
        $entity = new MigrationEntity();
        $entity->endVersion = 9;
        $entity->resumeScript = TestMigrationScript::class;
        $migrationScripts = self::runMethodAsPublic('getMigrationScripts', $entity);
        verify(get_class($migrationScripts[0]))->equals(TestMigrationScript::class);
        verify(count($migrationScripts))->equals(5);

        $setUpScripts();
        $entity = new MigrationEntity();
        $entity->endVersion = 9;
        $entity->skipScripts = [TestMigrationScript::class];
        $migrationScripts = self::runMethodAsPublic('getMigrationScripts', $entity);
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

    public function testInvalidScriptsFail()
    {
        MigrationsSettings::singleton()->setLocalVersion(1);
        $migrationScripts = [];
        foreach (range(1, 3) as $version) {
            $migrationScripts[] = $this->newScript($version);
        }
        $migrationScripts[] = 'Foo/Bar.php';
        $this->manager->setMigrationScripts($migrationScripts);

        $this->expectException(ImplementationException::class);
        RunMigrationsUseCase::execute($this->makeEntity(2));
    }

    public function testApplicationVersionIncreases()
    {
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

    public function testResumeOnScript()
    {
        foreach (range(1, 6) as $number) {
            $scripts[] = $this->newScript($number, 99, function () {
                $this->fail('Scripts before the resume point should never run');
            });
        }
        $scripts[] = new TestMigrationScript();
        $count = 0;
        foreach (range(6, 9) as $number) {
            $scripts[] = $this->newScript($number, 1, function () use (&$count) {
                return $count++;
            });
        }

        $this->manager->setMigrationScripts($scripts);
        $entity = $this->makeEntity(10, 0, TestMigrationScript::class);
        RunMigrationsUseCase::execute($entity);

        verify($count)->equals(4);
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

        $this->stateProvider->setLocalVersion(1);
        $this->manager->setMigrationScripts($scripts);
        $entity = $this->makeEntity(7, 1);
        $entity->skipScripts[] = TestMigrationScript::class;
        RunMigrationsUseCase::execute($entity);
    }

    public function testExecutionStopsOnError()
    {
        $msg = "";
        $this->manager->setMigrationScripts(
            [
                $this->newScript(1),
                $this->newScript(2),
                $this->newScript(3),
                $script = $this->newScript(4, 1, function () use (&$msg) {
                    throw new \Error($msg = "Error Thrown");
                }),
                $this->newScript(5, 1, function () {
                    throw new LoginFailedException("I break things");
                })
            ]
        );

        try {
            RunMigrationsUseCase::execute($this->makeEntity(6, 1));
        } catch (LoginFailedException $exception) {
            $this->fail("Failed to stop migration on error");
        }

        verify($msg)->equals("Error Thrown");
        verify($this->stateProvider->getResumeScript())->equals(get_class($script));
    }

    protected static function runMethodAsPublic($method, ...$params)
    {
        try {
            $class = new \ReflectionClass(RunMigrationsUseCase::class);
        } catch (\ReflectionException $e) {
            self::fail('Test not set up correctly.');
        }
        $method = $class->getMethod($method);
        $method->setAccessible(true);
        return $method->invoke(null, ...$params);
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
     * @param int    $localVersion
     * @param int    $targetVersion
     * @param string $resumeScript
     * @return MigrationEntity
     */
    protected function makeEntity($targetVersion = null, $localVersion = null, $resumeScript = null)
    {
        $entity = new MigrationEntity();
        if (isset($localVersion)) {
            $entity->startVersion = $localVersion;
        }
        $entity->endVersion = $targetVersion;
        $entity->resumeScript = $resumeScript;
        return $entity;
    }
}
