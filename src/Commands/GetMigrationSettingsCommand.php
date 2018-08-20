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
        $padr = function ($str, $padlen = 25) {
            return str_pad($str, $padlen, ' ', STR_PAD_RIGHT);
        };
        $migrationSettings = MigrationsSettings::singleton();

        $tag = function ($string, $tag) {
            return "<$tag>$string</$tag>";
        };

        $output->getFormatter()->setStyle('b', new OutputFormatterStyle('green', null, ['bold', 'underscore']));
        $output->writeln(
            $tag("Current Migration Settings:", 'b')
        );
        $output->getFormatter()->setStyle('i', new OutputFormatterStyle('green'));
        $output->writeln(
            $tag($padr("Application Version:") . Application::current()->getVersion(), "i")
        );
        $output->writeln(
            $tag($padr("Local Version:") . $migrationSettings->getLocalVersion(), 'i')
        );
        $output->writeln(
            $tag($padr("Resume Script:") . ($migrationSettings->getResumeScript() ?? 'none'), 'i')
        );
        $output->writeln(
            $tag($padr("Page Size:") . $migrationSettings->pageSize, 'i')
        );
        $output->writeln(
            $tag($padr("Repository Type:") . $migrationSettings->repositoryType, 'i')
        );
    }
}