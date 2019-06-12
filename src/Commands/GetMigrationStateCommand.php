<?php


namespace Rhubarb\Modules\Migrations\Commands;


use Rhubarb\Crown\Application;
use Rhubarb\Custard\Command\CustardCommand;
use Rhubarb\Modules\Migrations\MigrationsSettings;
use Rhubarb\Modules\Migrations\MigrationsStateProvider;
use Rhubarb\Stem\Custard\RequiresRepositoryCommand;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GetMigrationStateCommand extends RequiresRepositoryCommand
{
    protected function configure()
    {
        $this->setName('migrations:settings')
            ->setDescription('Output the current local and application versions');
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
            $tag("Current Migration State:", 'b')
        );
        $output->getFormatter()->setStyle('i', new OutputFormatterStyle('green'));
        $output->writeln(
            $tag($padr("Application Version:") . Application::current()->getVersion(), "i")
        );
        $output->writeln(
            $tag($padr("Local Version:") . $migrationStateProvider->getLocalVersion(), 'i')
        );

    }
}