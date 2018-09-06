<?php


namespace Rhubarb\Modules\Migrations\Commands;


use PHPUnit\Runner\Exception;
use Rhubarb\Crown\Application;
use Rhubarb\Crown\Exceptions\ImplementationException;
use Rhubarb\Custard\Command\CustardCommand;
use Rhubarb\Modules\Migrations\MigrationsSettings;
use Rhubarb\Modules\Migrations\MigrationsStateProvider;
use Rhubarb\Modules\Migrations\UseCases\RunMigrationsUseCase;
use Rhubarb\Modules\Migrations\UseCases\MigrationEntity;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class MigrateCommand extends CustardCommand
{
    const ARG_TARGET_VERSION = 'target-version';
    const ARG_START_VERSION = 'start-version';
    const OPT_SKIP_SCRIPTS = 'skip-scripts';
    const OPT_RESUME = 'resume';

    protected function configure()
    {
        $this->setName('migrations:migrate')
            ->setDescription('Update local version number and execute relevant migration scripts')
            ->addArgument(self::ARG_TARGET_VERSION, InputArgument::OPTIONAL,
                'A target application version to migrate towards.')
            ->addArgument(self::ARG_START_VERSION, InputArgument::OPTIONAL,
                'where to begin migration. Defaults to local version if exists.')
            ->addOption(self::OPT_SKIP_SCRIPTS, 's', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'scripts which should *not* be run as part of this migration.', [])
            ->addOption(self::OPT_RESUME, 'r', InputOption::VALUE_NONE,
                'should the migration continue from the previous migration attempt');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $migrationStateProvider = MigrationsStateProvider::getProvider();

        $targetVersion =
            $input->getArgument(self::ARG_TARGET_VERSION)
                ?: Application::current()->getVersion();

        $startVersion =
            $input->getArgument(self::ARG_START_VERSION)
                ?: $migrationStateProvider->getLocalVersion();

        $skipScripts = $input->getOption(self::OPT_SKIP_SCRIPTS);

        $entity = new MigrationEntity();
        $entity->startVersion = $startVersion;
        $entity->endVersion = $targetVersion;
        $entity->skipScripts = $skipScripts ?? [];
        $entity->resume = $input->getOption(self::OPT_RESUME) ?? false;

        try {
            RunMigrationsUseCase::execute($entity);
        } catch (\Error $error) {
            echo 'ERROR: ' . $error->getMessage();
        } catch (Exception $exception) {
            echo 'EXCEPTION: ' . $exception->getMessage();
        }
    }
}