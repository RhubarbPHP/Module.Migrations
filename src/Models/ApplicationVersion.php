<?php

namespace Rhubarb\Modules\Migrations\Models;

use Rhubarb\Crown\Xml\Node;
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
