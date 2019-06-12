<?php


namespace Rhubarb\Modules\Migrations\Commands;


use PHPUnit\Runner\Exception;
use Rhubarb\Custard\Command\CustardCommand;
use Rhubarb\Modules\Migrations\MigrationsStateProvider;
use Rhubarb\Stem\Custard\RequiresRepositoryCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunMigrationsCommand extends RequiresRepositoryCommand
{
    const ARG_TARGET_VERSION = 'target-version';
    const ARG_START_VERSION = 'start-version';
    const OPT_SKIP_SCRIPTS = 'skip-scripts';
    const OPT_RESUME = 'resume';

    protected function configure()
    {
        $this->setName('migrations:migrate')
            ->setDescription('Update local version number and execute relevant migration scripts')
            ->addOption(self::OPT_SKIP_SCRIPTS, 's', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'scripts which should *not* be run as part of this migration.', []);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $migrationStateProvider = MigrationsStateProvider::getProvider();

        $startVersion = $migrationStateProvider->getLocalVersion();
        $endVersion = $migrationStateProvider::getApplicationVersion();
        $skipScripts = $input->getOption(self::OPT_SKIP_SCRIPTS) ?? [];

        try {
            $migrationStateProvider->runMigrations($startVersion, $endVersion, $skipScripts);
        } catch (\Error $error) {
            echo 'ERROR: ' . $error->getMessage();
        } catch (Exception $exception) {
            echo 'EXCEPTION: ' . $exception->getMessage();
        }
    }
}