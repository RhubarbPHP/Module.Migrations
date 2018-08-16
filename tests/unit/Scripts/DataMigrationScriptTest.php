<?php


namespace Rhubarb\Scaffolds\Migrations\Tests\Scripts;


use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Scaffolds\Migrations\Tests\Fixtures\MigrationsTestCase;
use Rhubarb\Scaffolds\Migrations\Tests\Fixtures\TestDataMigrationScript;
use Rhubarb\Scaffolds\Migrations\Tests\Fixtures\TestUser;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Schema\Columns\StringColumn;

class DataMigrationScriptTest extends MigrationsTestCase
{
    public function testUpdateEnum()
    {
        $script = new TestDataMigrationScript();
        $this->populateUsers();

        $verifyCount = function ($search, $count) {
            verify(TestUser::find(new Equals('status', $search))->count())->equals($count);
        };

        $verifyCount('online', 5);
        $verifyCount('affline', 5);
        verify(TestUser::all()[0]->getSchema()->getColumns()['status']->enumValues)->equals(['online', 'affline']);
        $script->performEnumUpdate(TestUser::class, 'status', 'affline', 'offline');
        $verifyCount('offline', 5);
        $verifyCount('affline', 0);
        $verifyCount('online', 5);
        verify(array_values(TestUser::all()[0]->getSchema()->getColumns()['status']->enumValues))->equals([
            'online',
            'offline'
        ]);

        try {
            $script->performEnumUpdate('lad', 'statis', 'affline', 'offline');
        } catch (ImplementationException $exception) {
            verify($exception->getMessage())->contains('model class');
            verify($exception->getMessage())->contains('lad');
        }

        try {
            $script->performEnumUpdate(TestUser::class, 'statis', 'affline', 'offline');
        } catch (ImplementationException $exception) {
            verify($exception->getMessage())->contains('column name');
            verify($exception->getMessage())->contains('statis');
        }

        try {
            $script->performEnumUpdate(TestUser::class, 'status', 'uffline', 'offline');
        } catch (ImplementationException $exception) {
            verify($exception->getMessage())->contains('current value');
            verify($exception->getMessage())->contains('uffline');
        }

        try {
            $script->performEnumUpdate(TestUser::class, 'name', 'affline', 'offline');
        } catch (ImplementationException $exception) {
            verify($exception->getMessage())->contains('column type');
            verify($exception->getMessage())->contains(StringColumn::class);
        }
    }

    public function testDuplicateColumnsAdded(){
        $script = new TestDataMigrationScript();
        $this->populateUsers(5);
        verify((new TestUser())->getSchema()->getColumns())->count(3);
        $script->performSplitColumn(
            TestUser::class,
            'name',
            [
                new StringColumn('name', 50),
                new StringColumn('initials', 50),
            ],
            function ($existingData) {
                return [$existingData, $existingData[0]];
            }
        );
        verify(TestUser::all()[0]->exportData())->count(4);
    }

    public function testPaging()
    {
        $this->populateUsers(701);
        (new TestDataMigrationScript())->performEnumUpdate(TestUser::class, 'status', 'affline', 'offline');
        verify(TestUser::find(new Equals('status', 'online'))->count())->equals(350);
        verify(TestUser::find(new Equals('status', 'offline'))->count())->equals(351);
    }

    /**
     * @throws ImplementationException
     */
    public function testSplitColumn()
    {
        $script = new TestDataMigrationScript();
        $this->populateUsers();
        foreach (TestUser::all() as $user) {
            verify($user->name)->matchesFormat('forename%x%wsurname%x');
        }
        $script->performSplitColumn(
            TestUser::class,
            'name',
            [
                new StringColumn('forename', 50),
                new StringColumn('surname', 50),
            ],
            function ($existingData) {
                return explode(' ', $existingData);
            }
        );

        foreach (TestUser::all() as $user) {
            verify(strpos($user->name, $user->forename))->equals(0);
            verify(strpos($user->name, $user->surname))->greaterThan(0);
        }

        try {
            $script->performSplitColumn(
                'ScHLAAA',
                'name',
                [
                    new StringColumn('forename', 50),
                    new StringColumn('surname', 50),
                ],
                function ($existingData) {
                    return explode(' ', $existingData);
                });
        } catch (ImplementationException $exception) {
            verify($exception->getMessage())->contains('ScHLAAA');
        }

        try {
            $script->performSplitColumn(
                TestUser::class,
                'name',
                [
                    new StringColumn('surname', 50),
                ],
                function ($existingData) {
                    return explode(' ', $existingData);
                });
        } catch (ImplementationException $exception) {
            verify($exception->getMessage())->contains('data returned for');
        }

        try {
            $script->performSplitColumn(
                TestUser::class,
                'name',
                [
                    new StringColumn('forename', 50),
                    new StringColumn('surname', 50),
                ],
                function ($existingData) {
                    return explode(' ', $existingData)[0];
                });
        } catch (ImplementationException $exception) {
            verify($exception->getMessage())->contains('data returned for');
        }
    }

    private function populateUsers($number = null)
    {
        foreach (range(1, $number ?? 10) as $number) {
            $user = new TestUser();
            $user->name = uniqid('forename') . ' ' . uniqid('surname');
            $user->status = ['online', 'affline'][$number % 2];
            $user->save();
        }
    }
}