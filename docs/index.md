Application Versions
====================

It's a common requirement that when applications are upgraded there will sometimes be breaking changes that
require a scripted solution. This scaffold provides a framework for versioning your application and
applying upgrade scripts when relevant.

The version history is stored in a repository model so if you are using a SaaS platform this scaffold should
still be suitable.

## Setting the application version

The current application version needs set in your `app.config.php`

~~~php
class MyApp extends Module
{
    public function initialiseModule()
    {
        $applicationVersionSettings = new ApplicationVersionSettings();
        $applicationVersionSettings->CurrentVersion = 1.21;
    }
}
~~~

The version number is simply a float with larger numbers being a newer version.

## Creating an upgrade script

Create a new class that extends VersionUpgradeScript:

~~~php
class ContactNameSplitting extends VersionUpgradeScript
{
    public function __construct()
    {
        // Pass the version number at which the script should be ran
        parent::__construct( 1.22 );
    }

    public function doUpgrade()
    {
        // Code for the upgrade goes here.
    }
}
~~~