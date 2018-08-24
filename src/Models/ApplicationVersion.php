<?php

namespace Rhubarb\Scaffolds\Migrations\Models;

use Rhubarb\Stem\Models\Model;
use Rhubarb\Stem\Schema\Columns\AutoIncrementColumn;
use Rhubarb\Stem\Schema\ModelSchema;

class ApplicationVersion extends Model
{
    protected function createSchema()
    {
        $schema = new ModelSchema('tblApplicationVersion');

        $schema->addColumn(
            new AutoIncrementColumn('ApplicationVersionID')
        );

        return $schema;
    }
}
