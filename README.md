Application Versions
====================

Commonly, when applications are upgraded there are breaking changes that require a scripted solution. This module provides a framework to: quickly generate migration scripts; manage the local version of an instance of your application, and; run migration scripts in order to bring your local version up-to-date with the current project version. 

The version history is stored in a repository model so if you are using a SaaS platform this scaffold should
still be suitable.

## Setting the application version

The current application version needs to be defined when your project is initialized. The version must be an integer. 

~~~php
class MyApplication extends Application
{
    public function initialise()
    {
        ...
        $this->version = 12; 
        ...
    }
}
~~~

## Creating a migration script

To create a new migration script simply create a class that implements the MigrationScript interface. 

**execute()** is the actual logic of your script.

**version()** defines on which application version this script should be executed. Returns an int.
 
**priority()** determines the order in which scripts for the same version should be executed. Returns an int.

~~~php
class ImageDeletionScript extends VersionUpgradeScript
{
        public function execute()
        {
            foreach (Image::all(new Equals('active', false)) as $image) {
                unlink($image->filePath);
                $image->delete();
            }
        }
    
        public function version(): int
        {
            return 17;
        }

        public function priority(): int
        {
            return 0;
        }
}
~~~

## Registering your script

Scripts will not be ran unless they are registered. Scripts can be registered by calling `registerMigrationScripts($scriptsArray)` on MigrationsModule.  


```$php
   MigrationsManager::getMigrationsManager()->registerMigrationScripts([
       SplitNameColumnScript::class,
       DeleteAllImagesScript::class,
       UpdatedGdprInfoScript::class
   ]);
```

## Data Migration Scripts

To reduce repeating code you can also extend the DataMigrationScript class. This class has methods already created to allow you to perform common migration patterns quickly. 

DataMigrationScripts look the exact same as regular Migrations. You can call the inherited methods to perform tasks for you.  

~~~php
class ContactNameSplitting extends DataMigrationScript
{
        public function execute()
        {
            foreach (Image::all(new Equals('active', false)) as $image) {
                unlink($image->filePath);
                $image->delete();
            }
            
            try {
                $this->updateEnumOption(
                    User::class,
                    'status',
                    'on line',
                    'online'
                );
            } catch (\Rhubarb\Crown\Exceptions\ImplementationException $e) {
            }
        }
    
        public function version(): int
        {
            return 18;
        }

        public function priority(): int
        {
            return 10;
        }
}
~~~