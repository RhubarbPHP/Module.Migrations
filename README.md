Migrations Module
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


~~~php
   MigrationsManager::getMigrationsManager()->registerMigrationScripts([
       SplitNameColumnScript::class,
       DeleteAllImagesScript::class,
       UpdatedGdprInfoScript::class
   ]);
~~~

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

## Custard Commands

### migrations:migrate

##### Parameters

Parameters must be given in order. 

| Parameter | Description | Default |
| --- | --- | --- | 
| Target Version | Which version migrations should be ran up to | current application version |
| Starting Version | Where migrations should start from | current local version |

##### Options

| Option | shortcut |  Description | 
| --- | :---: | --- | 
| Skip Scripts | -s | It is possible to skip certain scripts, for example if you are re-running a failed migration and do not want to include the failing script. To do so simply include teh option -s with the next input being the script to skip. You can include multiple scripts | 
| Resume | -r | If a previous migration failed you can fix the issue and resume the migratino from that point. |

##### Examples

Migrate to version 13 from current: `/vagrant/bin/custard migrations:migrate 13`

Migrate from version 14 to 18: `/vagrant/bin/custard migrations:migrate 18 14`

Resume migrating to version 18 skipping two scripts: 

`/vagrant/bin/custard migrations:migrate -r 18 -s My\Project\Scripts\NewMigrationScript.php -s My\Project\Scripts\DestroyOldDataScript.php`

### migrations:run-script

This command simply takes the class of a script and executes it immediately.

##### Example

Run NewMigrationScript 

`/vagrant/bin/custard migrations:run-script My\Project\Scripts\NewMigrationScript.php` 

## Configuration

There are a number of settings that can be customized with the MigrationsSettings class. 


| Setting | Description | Default |
| --- | --- | --- |
| setLocalVersionPath() | Used to set the path and name of the file that stores the Local Version number.  | System temp directory | 
| setResumeScriptPath() | Used to set the path and name of the file that stores the resume point for if a migration fails | System temp directory | 
| pageType | When a DataMigrationScript needs to iterate over large collections it will page based on this setting. | 100 | 
| RepositoryType | Informs the migration module which repositry type is in use. | MySql::class | 

~~~php
...
$migrationSettings = MigrationsSettings::singleton();
$migrationSettings->setLocalVersionPath(__DIR__ . 'local-version.lock');
$migrationSettings->setResumeScriptPath(__DIR__ . 'resume-script.lock');
$migrationSettings->repositoryType = MySql::class;
$migrationSettings->pageSize = 1000; 
...
~~~ 