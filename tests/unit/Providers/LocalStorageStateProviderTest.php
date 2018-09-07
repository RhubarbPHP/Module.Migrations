<?php


namespace Rhubarb\Modules\Migrations\Providers;


use Rhubarb\Modules\Migrations\Tests\Fixtures\MigrationsTestCase;
use Rhubarb\Modules\Migrations\Tests\Fixtures\TestMigrationScript;
use Rhubarb\Modules\Migrations\UseCases\RunMigrationsUseCase;

class LocalStorageStateProviderTest extends MigrationsTestCase
{
    public function testResumeOnScript()
    {
        foreach (range(1, 5) as $number) {
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

        $this->manager->registerMigrationScripts($scripts);
        $entity = $this->makeEntity(10, 6, TestMigrationScript::class);
        RunMigrationsUseCase::execute($entity);

        verify($count)->equals(4);
    }
}