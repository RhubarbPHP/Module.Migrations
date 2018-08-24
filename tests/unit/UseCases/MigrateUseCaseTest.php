<?php /** @noinspection PhpUnhandledExceptionInspection */

namespace Rhubarb\Scaffolds\Migrations\Tests\UseCases;

use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Crown\LoginProviders\Exceptions\LoginFailedException;
use Rhubarb\Scaffolds\Migrations\MigrationsSettings;
use Rhubarb\Scaffolds\Migrations\Scripts\MigrationScriptInterface;
use Rhubarb\Scaffolds\Migrations\Tests\Fixtures\MigrationsTestCase;
use Rhubarb\Scaffolds\Migrations\Tests\Fixtures\TestMigrationScript;
use Rhubarb\Scaffolds\Migrations\Tests\Fixtures\TestMigrationsManager;
use Rhubarb\Scaffolds\Migrations\UseCases\MigrateToVersionUseCase;
use Rhubarb\Scaffolds\Migrations\UseCases\MigrationEntity;

class MigrateUseCaseTest extends MigrationsTestCase
{
    /** @var TestMigrationsManager $manager */
    protected $manager;
    /** @var MigrationsSettings $settings */
    protected $settings;

    public function testLocalVersionIncreases()
    {
        $this->settings->setLocalVersion(1);
        $this->manager->setMigrationScripts([$this->newScript(6)]);
        MigrateToVersionUseCase::execute($this->makeEntity(7));
        verify($this->settings->getLocalVersion())->equals(7);
    }

    public function testMigrationScriptsRetrieved()
    {
        MigrationsSettings::singleton()->setLocalVersion(77);

        $entity = new MigrationEntity();
        $entity->localVersion = 1;
        $entity->targetVersion = 1000;

        /** @var MigrationScriptInterface[] $migrationScripts */
        $migrationScripts = self::runMethodAsPublic('getMigrationScripts', $entity);
        verify($migrationScripts)->isEmpty();

        foreach ([78, 80, 80, 81] as $version) {
            $migrationScripts[] = $this->newScript($version);
        }
        $this->manager->setMigrationScripts($migrationScripts);
        $entity->localVersion = 79;
        $entity->targetVersion = 80;
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
        verify($migrationScripts[0]->version())->equals(79);
        verify($migrationScripts[1]->version())->equals(80);
        verify($migrationScripts[1]->priority())->greaterThan($migrationScripts[2]->priority());
        verify($migrationScripts[2]->version())->equals(80);

        $setUpScripts = function () use (&$loop) {
            MigrationsSettings::singleton()->setLocalVersion(4);
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
        $entity->targetVersion = 9;
        $entity->resumeScript = TestMigrationScript::class;
        $migrationScripts = self::runMethodAsPublic('getMigrationScripts', $entity);
        verify(get_class($migrationScripts[0]))->equals(TestMigrationScript::class);
        verify(count($migrationScripts))->equals(5);

        $setUpScripts();
        $entity = new MigrationEntity();
        $entity->targetVersion = 9;
        $entity->skipScripts = [TestMigrationScript::class];
        $migrationScripts = self::runMethodAsPublic('getMigrationScripts', $entity);
        foreach ($migrationScripts as $migrationScript) {
            verify($migrationScript)->isNotInstanceOf(TestMigrationScript::class);
        }
    }

    public function testScriptsRunInOrder()
    {
        MigrationsSettings::singleton()->setLocalVersion(1);
        $migrationScripts = [];
        $scriptsRan = $loop = 0;
        foreach ([0, 1, 0, 2, 1, 4] as $version) {
            $migrationScripts[] = $this->newScript($version, $loop++, function () use (&$scriptsRan) {
                return $scriptsRan++;
            });
        }
        $this->manager->setMigrationScripts($migrationScripts);

        MigrateToVersionUseCase::execute($this->makeEntity(2));
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
        MigrateToVersionUseCase::execute($this->makeEntity(2));
    }

    public function testApplicationVersionIncreases()
    {
        MigrationsSettings::singleton()->setLocalVersion(1);
        MigrateToVersionUseCase::execute($this->makeEntity(2));
        verify(MigrationsSettings::singleton()->getLocalVersion())->equals(2);

        // Version doesn't increase on errors.
        $script = $this->newScript(2, 1, function () {
            throw new ImplementationException('test');
        });
        $this->manager->setMigrationScripts([$script]);
        $this->setExpectedException(ImplementationException::class);
        MigrateToVersionUseCase::execute($this->makeEntity(3));
        verify(MigrationsSettings::singleton()->getLocalVersion())->equals(2);
    }

    public function testResumeScriptSaves()
    {
        $failingScript = $this->newScript(
            4,
            5,
            function () {
                throw new \Error("hi i'm an error");
            }
        );

        $this->manager->setMigrationScripts([$failingScript]);

        MigrateToVersionUseCase::execute($this->makeEntity(6, 3));
        verify($this->settings->getResumeScript())->equals(get_class($failingScript));
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
        MigrateToVersionUseCase::execute($entity);

        verify($count)->equals(4);
    }

    public function testSkipScripts() {
        foreach (range(1, 6) as $number) {
            $scripts[] = $this->newScript($number);
        }
        $failScript = new TestMigrationScript();
        $failScript->execute = function () {
            $this->fail('This script should not be run!');
        };

        $this->settings->setLocalVersion(1);
        $this->manager->setMigrationScripts($scripts);
        $entity = $this->makeEntity(7, 1);
        $entity->skipScripts[] = TestMigrationScript::class;
        MigrateToVersionUseCase::execute($entity);
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
            MigrateToVersionUseCase::execute($this->makeEntity(6, 1));
        } catch (LoginFailedException $exception) {
            $this->fail("Failed to stop migration on error");
        }

        verify($msg)->equals("Error Thrown");
        verify($this->settings->getResumeScript())->equals(get_class($script));
    }

    protected static function runMethodAsPublic($method, ...$params)
    {
        try {
            $class = new \ReflectionClass(MigrateToVersionUseCase::class);
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
            $entity->localVersion = $localVersion;
        }
        $entity->targetVersion = $targetVersion;
        $entity->resumeScript = $resumeScript;
        return $entity;
    }
}
