# Change log

### 1.0.2

* Changed:  Custard commands are now RequiresRepositoryCommands to ensure more
            exotic setups have their Stem connections configured if using the
            DatabaseMigrationsStateProvider.

### 1.0.1

* Changed:  Fixed to support PHP 7.0
* Changed:  Now requires a list of migration classes, not objects (performance)

### 1.0.0

* Added:    Local Versioning
* Added:    MigrationScript
* Added:    DataMigrationScripts
* Added:    Tests!
