Migrations Module
====================

Commonly, when applications are upgraded there are breaking changes that require a scripted solution. This module provides a framework to: quickly generate migration scripts; manage the local version of an instance of your application, and; run migration scripts in order to bring your local version up-to-date with the current project version. 


## Setting the application version

The current application version needs to be defined when your project is initialized. The version must be an integer. 

~~~php
class MyApplication extends Application
{
    public function initialise()
    {
        // ...
        $this->version = 12; 
        // ...
    }
}
~~~

## Creating a migration script

A migration script must implement the MigrationScriptInterface. 

**execute()** is the logic of the migration.

**version()** must return an integer and defines on which application version this script should be executed. Migrations are executed in an order of version. Migrations on the same version are executed in the order they are registered. 

~~~php
class ImageDeletionScript extends MigrationScript
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
}
~~~

## Registering your script

Scripts will not be ran unless they are registered. Scripts are registered by calling `registerMigrationScripts($scriptsArray)` on MigrationsModule.  

~~~php
   MigrationsManager::getMigrationsManager()->registerMigrationScripts([
       SplitNameColumnScript::class,
       DeleteAllImagesScript::class,
       UpdatedGdprInfoScript::class
   ]);
~~~

## Custard Commands

### migrations:migrate

The primary migrate command. All registered migration scripts with a version of or between the current locally stored version and the application version defined in code will be loaded and executed. 

##### Options

| Option | shortcut |  Description | 
| --- | :---: | --- | 
| Skip Scripts | -s | It is possible to skip certain scripts, for example if you are re-running a failed migration and do not want to include the failing script. To do so include the option -s followed by the full path of the script to skip. You can include multiple scripts with -s. |

##### Example
 
`custard migrations:migrate -s My\Project\Scripts\NewMigrationScript.php -s My\Project\Scripts\DestroyOldDataScript.php`

### migrations:run-script

Takes the full path of a script and immediately executes it. 

##### Example

`custard migrations:run-script My\Project\Scripts\NewMigrationScript.php` 

## MigrationsManager

The MigrationsManager is used to register and retrieve Migration Scripts. It's core function is to provide a set of active, valid and ordered migration scripts within a range via the `getMigrationScripts()` function.

The MigrationsManager implemented the Singleton trait and can be accessed via `MigrationsManager::getMigrationsManager()`

## MigrationsStateProvider

The `MigrationsStateProvider` handles the current local version of the application and which migration scripts have been run. It must implement the following methods:

`getLocalVersion()` retrieves the local version. Must return an integer. 

`setLocalVersion(int $newLocalVersion)` updates the local version.

`markScriptCompleted(MigrationScriptInterface $migrationScript)` locally stores a script as having been successfully executed.

`isScriptComplete(string $className)` checks if a script has already been successfully executed. 

`getCompletedScripts()`