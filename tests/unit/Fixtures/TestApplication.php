<?php

namespace Rhubarb\Modules\Migrations\Tests\Fixtures;

use Rhubarb\Crown\Application;

class TestApplication extends Application
{
    public function setVersion(int $version) {
        $this->version = $version;
    }
}