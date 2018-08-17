<?php


namespace Rhubarb\Scaffolds\Migrations\Commands;


use Rhubarb\Crown\Application;
use Rhubarb\Custard\Command\CustardCommand;
use Rhubarb\Scaffolds\Migrations\MigrationsSettings;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetMigrationSettingsCommand extends CustardCommand
{
    protected function configure()
    {
        $this->setName('migrations:settings')
            ->setDescription('Get the current local version, application version and resume script');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln("<info>Current Migration Settings:</info>");
        $output->writeln("      Application Version: " . Application::current()->getVersion());
        $migrationSettings = MigrationsSettings::singleton();
        $output->writeln("      Local Version: " . $migrationSettings->getLocalVersion());
        $output->writeln("      Resume Script: " . $migrationSettings->getResumeScript());
        $output->writeln("      Page Size: " . $migrationSettings->pageSize);
        $output->writeln("      Repository Type: " . $migrationSettings->repositoryType);
    }


}