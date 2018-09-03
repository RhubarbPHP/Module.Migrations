<?php


namespace Rhubarb\Modules\Migrations\Commands;


use Rhubarb\Crown\Application;
use Rhubarb\Custard\Command\CustardCommand;
use Rhubarb\Modules\Migrations\MigrationsSettings;
use Rhubarb\Modules\Migrations\MigrationsStateProvider;
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
        $migrationStateProvider = MigrationsStateProvider::getProvider();

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
            $tag($padr("Local Version:") . $migrationStateProvider->getLocalVersion(), 'i')
        );
        $output->writeln(
            $tag($padr("Resume Script:") . ($migrationStateProvider->getResumeScript() ?? 'none'), 'i')
        );
    }
}