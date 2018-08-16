<?php /** @noinspection PhpUndefinedClassInspection */

use Rhubarb\Scaffolds\Migrations\Scripts\DataMigrationScript;

class ExampleDataMigrationScript extends DataMigrationScript
{

    /**
     * Primary logic of the script should be implemented or called here.
     */
    public function execute()
    {
        try {
            /**
             * Update an Enum Option
             *
             * This method will update the 'status' column in the table representing the User model.
             * Every row with the value 'on line' will be updated to be 'online' and the definition of the
             * column will be update to remove the current value and add the new value.
             */
            $this->updateEnumOption(
                User::class,
                'status',
                'on line',
                'online'
            );
        } catch (\Rhubarb\Crown\Exceptions\ImplementationException $e) {
            // ...
        }

        /**
         * Split Column
         *
         * This method will add the new columns provided to the table representing the 'User' model.
         * For each record in the table the value in the field 'Name' will be passed to the provided callable. The
         * callable must return an array of values to be inserted into the provided newColumns, in the same order as the
         * newColumns were provided.
         *
         * To use an existing column as one of the new columns simple use the *exact* same name for the column.
         */
        try {
            $this->splitColumn(
                User::class,
                'Name',
                [
                    new StringColumn('forename', 100),
                    new StringColumn('surname', 100)
                ],
                function ($currentValue) {
                    return explode(' ', $currentValue);
                }
            );
        } catch (\Rhubarb\Crown\Exceptions\ImplementationException $e) {
            // ...
        }

    }

    /**
     * The application version this script should be ran on
     *
     * @return int
     */
    public function version(): int
    {
        return 17;
    }

    /**
     * Implement this method to set the priority of a script.
     * Scripts with higher priority are ran before other scripts for the same application version.
     *
     * @return int
     */
    public function priority(): int
    {
        return 10;
    }
}