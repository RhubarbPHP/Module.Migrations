<?php


namespace Rhubarb\Scaffolds\Migrations\Tests\Scripts;


use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Scaffolds\Migrations\MigrationsSettings;
use Rhubarb\Scaffolds\Migrations\Tests\Fixtures\MigrationsTestCase;
use Rhubarb\Scaffolds\Migrations\Tests\Fixtures\TestDataMigrationScript;
use Rhubarb\Scaffolds\Migrations\Tests\Fixtures\TestMigrationsManager;
use Rhubarb\Scaffolds\Migrations\Tests\Fixtures\TestUser;
use Rhubarb\Stem\Filters\Equals;
use Rhubarb\Stem\Schema\Columns\StringColumn;

class DataMigrationScriptTest extends MigrationsTestCase
{
    /** @var TestMigrationsManager $manager */
    protected $manager;
    /** @var MigrationsSettings $settings */
    protected $settings;

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
        } catch (\Error $exception) {
            verify($exception->getMessage())->contains('lad');
            verify($exception->getMessage())->contains('model class');
        }

        try {
            $script->performEnumUpdate(TestUser::class, 'statis', 'affline', 'offline');
        } catch (\Error $exception) {
            verify($exception->getMessage())->contains('column name');
            verify($exception->getMessage())->contains('statis');
        }

        try {
            $script->performEnumUpdate(TestUser::class, 'status', 'uffline', 'offline');
        } catch (\Error $exception) {
            verify($exception->getMessage())->contains('current value');
            verify($exception->getMessage())->contains('uffline');
        }

        try {
            $script->performEnumUpdate(TestUser::class, 'name', 'affline', 'offline');
        } catch (\Error $exception) {
            verify($exception->getMessage())->contains('column type');
            verify($exception->getMessage())->contains(StringColumn::class);
        }
    }

    public function testDuplicateColumnsAdded()
    {
        $script = new TestDataMigrationScript();
        $this->populateUsers(5);
        $columnsCount = $this->countColumns(TestUser::class);
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
        verify($this->countColumns(TestUser::class))->equals($columnsCount + 1);
    }

    public function testRenameColumn()
    {
        $script = new TestDataMigrationScript();
        $this->populateUsers(3);
        foreach (TestUser::all() as $testUser) {
            $name[] = $testUser->name;
        }
        $script->performRenameColumn(
            TestUser::class,
            'name',
            'fullName'
        );
        foreach (TestUser::all() as $testUser) {
            verify($testUser->fullName)->equals(array_shift($name));
        }
    }

    public function testPaging()
    {
        $this->settings->pageSize = 50;
        $this->populateUsers(201);
        (new TestDataMigrationScript())->performEnumUpdate(TestUser::class, 'status', 'affline', 'offline');
        verify(TestUser::find(new Equals('status', 'online'))->count())->equals(100);
        verify(TestUser::find(new Equals('status', 'offline'))->count())->equals(101);
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
        } catch (\Error $error) {
            verify($error->getMessage())->contains('ScHLAAA');
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
        } catch (\Error $error) {
            verify($error->getMessage())->contains('data returned for');
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
        } catch (\Error $error) {
            verify($error->getMessage())->contains('data returned for');
        }
    }

    public function testMergeColumns()
    {
        $this->populateUsers(10);
        $columnsCount = $this->countColumns(TestUser::class);
        $script = new TestDataMigrationScript();

        $testUser = TestUser::all()[0];
        $testUser->houseNumber = 10;
        $testUser->street = "Fleet Street";
        $testUser->town = "Arkham";
        $testUser->save();

        $script->performMergeColumns(
            TestUser::class,
            [
                'houseNumber',
                'street',
                'town'
            ],
            new StringColumn('address', 150),
            function ($a, $b, $c) {
                return implode(', ', func_get_args());
            }
        );

        verify(TestUser::all()[1]->address)->notEmpty();
        verify(TestUser::all()[0]->address)->equals("10, Fleet Street, Arkham");
        verify($this->countColumns(TestUser::class))->equals($columnsCount + 1);

        try {
            $script->performMergeColumns(
                TestUser::class,
                [
                    'houseName',
                    'street',
                ],
                new StringColumn('address', 150),
                function ($a, $b) {
                    return implode(', ', func_get_args());
                }
            );
            $this->fail('invalid column names should cause an error');
        } catch (\Error $exception) {
            verify($exception->getMessage())->contains('houseName');
        }

        $script->performMergeColumns(
            TestUser::class,
            [
                'houseNumber',
                'street',
                'town'
            ],
            new StringColumn('_address', 150),
            function ($a, $b, $c) {
                return implode(', ', func_get_args());
            }
        );

        // You can use existing columns
        verify(TestUser::all()[0]->_address)->equals("10, Fleet Street, Arkham");
    }

    /**
     * @param string $modelClass
     * @return int
     */
    private function countColumns(string $modelClass): int
    {
        return count((new $modelClass())->getSchema()->getColumns());
    }

    private function populateUsers($number = null)
    {
        foreach (range(1, $number ?? 10) as $number) {
            $user = new TestUser();
            $user->name = uniqid('forename') . ' ' . uniqid('surname');
            $user->status = ['online', 'affline'][$number % 2];
            $user->houseNumber = rand(0, 100) . ('abcdefghijklmnopqrstuvwxyz'[rand(0, 25)]);
            $user->street = implode(' ', [uniqid('street'), uniqid('street')]);
            $user->town = uniqid('town');
            $user->save();
        }
    }
}