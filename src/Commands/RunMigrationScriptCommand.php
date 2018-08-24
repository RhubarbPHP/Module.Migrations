<?php


namespace Rhubarb\Scaffolds\Migrations\Commands;


use Rhubarb\Custard\Command\CustardCommand;
use Rhubarb\Scaffolds\Migrations\MigrationsManager;
use Rhubarb\Scaffolds\Migrations\MigrationsSettings;
use Rhubarb\Scaffolds\Migrations\Scripts\MigrationScriptInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class RunMigrationScriptCommand extends CustardCommand
{
    const ARG_SCRIPT_CLASS = 'script-class';

    protected function configure()
    {
        $this->setName('migrations:run-script')
            ->setDescription('Run a specific Migration Script.')
            ->addArgument(self::ARG_SCRIPT_CLASS, InputArgument::OPTIONAL,
                'A script to run');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $scriptClass = $input->getArgument(self::ARG_SCRIPT_CLASS);
        if (is_null($scriptClass)) {
            $output->writeln("Availble Migration Scrips:");
            foreach (MigrationsManager::getMigrationsManager()->getMigrationScripts() as $script) {
                $output->writeln("    -  " . get_class($script));
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

        if (!($script instanceof MigrationsSettings)) {
            $output->writeln('Provided class does not implement Migration Script');
            return;
        }

        if ($script->version() < MigrationsSettings::singleton()->getLocalVersion()) {
            $runAnyway =
                $this->askChoiceQuestion('This script is for a version lower than the local application version. Run anyway?',
                    ['y', 'n'], 'n', true);
            if ($runaway = 'n') {
                $output->writeln('Outdated script. Abandoning execution.');
                return;
            }
        }

        $script->execute();
    }
}