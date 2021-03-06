<?php

/*
 *	Copyright 2015 RhubarbPHP
 *
 *  Licensed under the Apache License, Version 2.0 (the "License");
 *  you may not use this file except in compliance with the License.
 *  You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 *  Unless required by applicable law or agreed to in writing, software
 *  distributed under the License is distributed on an "AS IS" BASIS,
 *  WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 *  See the License for the specific language governing permissions and
 *  limitations under the License.
 */

namespace Rhubarb\Modules\Migrations;

use Rhubarb\Crown\Module;
use Rhubarb\Modules\Migrations\Commands\GetMigrationStateCommand;
use Rhubarb\Modules\Migrations\Commands\RunMigrationsCommand;
use Rhubarb\Modules\Migrations\Commands\RunSingleMigrationScriptCommand;

class MigrationsModule extends Module
{
    protected $migrationStateProviderClass;

    public function __construct(string $migrationStateProviderClass)
    {
        parent::__construct();
        $this->migrationStateProviderClass = $migrationStateProviderClass;
    }

    protected function initialise()
    {
        parent::initialise();

        MigrationsStateProvider::setProviderClassName($this->migrationStateProviderClass);
    }

    public function getCustardCommands()
    {
        return
            array_merge(
                parent::getCustardCommands(),
                [
                    new RunMigrationsCommand(),
                    new RunSingleMigrationScriptCommand(),
                    new GetMigrationStateCommand()
                ]
            );
    }
}