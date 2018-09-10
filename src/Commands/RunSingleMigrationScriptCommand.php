<?php


namespace Rhubarb\Modules\Migrations\Commands;


use Rhubarb\Custard\Command\CustardCommand;
use Rhubarb\Modules\Migrations\Interfaces\MigrationScriptInterface;
use Rhubarb\Modules\Migrations\MigrationsManager;
use Rhubarb\Modules\Migrations\MigrationsSettings;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunSingleMigrationScriptCommand extends CustardCommand
{
    const ARG_SCRIPT_CLASS = 'script-class';

    protected function configure()
    {
        $this->setName('migrations:run-script')
            ->setDescription('Run a specific Migration Script.')
            ->addArgument(self::ARG_SCRIPT_CLASS, InputArgument::OPTIONAL,
                'Full class name and path of a Migration Script to run.'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $scriptClass = $input->getArgument(self::ARG_SCRIPT_CLASS);
        if (is_null($scriptClass) || $scriptClass == 'list') {
            $output->writeln("Available Migration Scripts:");
            $manager = MigrationsManager::getMigrationsManager();
            foreach ($manager->getRegisteredMigrationScriptClasses() as $class) {
                $output->writeln("    -  " . $class);
            }
            return;
        }

        if (class_exists($scriptClass)) {
            /** @var MigrationScriptInterface $script */
            $script = new $scriptClass();
        } else {
            $output->writeln('Unknown script class provided');
            return;
        }

        if (!($script instanceof MigrationScriptInterface)) {
            $output->writeln('Provided class does not implement Migration Script');
            return;
        }

        $script->execute();
    }
}