<?php


namespace Rhubarb\Scaffolds\Migrations\Commands;


use Rhubarb\Crown\Application;
use Rhubarb\Custard\Command\CustardCommand;
use Rhubarb\Scaffolds\Migrations\MigrationsSettings;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
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
        $pad_right = function ($str) {
            return str_pad($str, 25, ' ', STR_PAD_RIGHT);
        };
        $migrationSettings = MigrationsSettings::singleton();

        $output->getFormatter()->setStyle('t', $outputStyle = new OutputFormatterStyle('green',null, ['bold']));
        $output->writeln("Current Migration Settings:");
        $output->getFormatter()->setStyle('info', new OutputFormatterStyle('green'));
        $output->writeln("      " . $pad_right("Application Version:") . Application::current()->getVersion());
        $output->writeln("      " . $pad_right("Local Version:") . $migrationSettings->getLocalVersion());
        $output->writeln("      " . $pad_right("Resume Script:") . ($migrationSettings->getResumeScript() ?? 'none'));
        $output->writeln("      " . $pad_right("Page Size:") . $migrationSettings->pageSize);
        $output->writeln("      " . $pad_right("Repository Type:") . $migrationSettings->repositoryType);
    }
}