<?php

namespace Rhubarb\Scaffolds\Migrations\Tests\Fixtures;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Repositories\MySql\Schema\Columns\MySqlEnumColumn;
use Rhubarb\Stem\Schema\Columns\AutoIncrementColumn;
use Rhubarb\Stem\Schema\Columns\StringColumn;
use Rhubarb\Stem\Schema\ModelSchema;

/**
 * Class TestUser
 *
 * @property int    $id
 * @property string $status
 * @property string $name
 *
 * @package Rhubarb\Scaffolds\Migrations\Tests\Fixtures
 */
class TestUser extends Model
{
    const VERSION = 1;

    protected function createSchema()
    {
        $schema = new ModelSchema('TestUser');
        /** @noinspection PhpUnhandledExceptionInspection */
        $schema->addColumn(
            new AutoIncrementColumn('id'),
            new MySqlEnumColumn('status', 'online', ['online', 'affline']),
            new StringColumn('name', 150)
        );
        return $schema;
    }
}
