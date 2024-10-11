<?php
/**
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file docs/licenses/LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to contact@h-hennes.fr so we can send you a copy immediately.
 *
 * @author    Hervé HENNES <contact@h-hhennes.fr>
 * @copyright since 2023 Hervé HENNES
 * @license   https://opensource.org/licenses/AFL-3.0  Academic Free License ("AFL") v. 3.0
 */

namespace Hhennes\PsMigrationUpgradeDb\Command;

use Hhennes\PsMigrationUpgradeDb\DbUpgrader\Upgrader;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

class ApplyPrestashopDbUpgrade extends Command
{
    const PS_VERSIONS_REGEXP = '#^(1\.7.[5-8].[0-9]{1,2}|8.[0-2].[0-9])#';

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('hhennes:psmigration:upgrade-db')
            ->setDescription('Apply db upgrade from current version to last version')
            ->addArgument('from-version', InputArgument::REQUIRED, 'Version from where the db upgrade should start')
            ->addArgument('to-version', InputArgument::REQUIRED, 'Last where the db upgrade should stop')
            ->addOption('get-version', null, InputOption::VALUE_NONE, 'Get the current db Version')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Get only the list of db upgrades to apply')
            ->setHelp(
                'This command allow to run db upgrade of the module autoupgrade without running it directly' . PHP_EOL
                . 'Thus it allows to push the code through CI/CD or to run this command to finish the upgrade after the push' . PHP_EOL
                . 'You can also get only the current db version with option --get-version' . PHP_EOL
                . 'And the list of upgrades which will be applied with the option --dry-run'
            );
    }

    /**
     * {@inheritdoc}
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($input->getOption('get-version')) {
            $output->writeln(sprintf('<info>Current Db installed version : %s</info>', $this->getCurrentVersion()));

            return 0;
        }
        if (!\Module::getInstanceByName('autoupgrade') || !\Module::isInstalled('autoupgrade')) {
            $output->writeln(
                '<error>The module autoupgrade is required and should be installed to use this tool</error>'
            );

            return 1;
        }

        $fromVersion = $input->getArgument('from-version');
        $toVersion = $input->getArgument('to-version');
        $dryRun = $input->getOption('dry-run');

        if (!$this->isvalidPsVersion($fromVersion) || !$this->isvalidPsVersion($toVersion)) {
            $output->writeln('<error>Please enter valid from and to versions</error>');

            return 1;
        }
        $output->writeln('<info>Upgrade process start</info>');
        try {
            $logger = new ConsoleLogger($output);
            $dbUpgrader = new Upgrader($logger);
            $dbUpgrader
                ->setOldVersion($fromVersion)
                ->setDestinationVersion($toVersion);
            if (false !== $dryRun) {
                $output->writeln('<comment>==== Dry run mode, nothing will be applied ===</comment>');
                $dbUpgrader->setRunMode(Upgrader::RUN_MODE_DRY_RUN);
            }
            $dbUpgrader->upgradeDb();
            $output->writeln(sprintf('<info>Db version %s applied with success</info>', $toVersion));
            \Configuration::updateValue('PS_VERSION_DB', $toVersion);
        } catch (\Throwable $e) {
            $output->writeln('<error>Unable to apply upgrade, please check logs</error>');
            $logger->error('Error : ' . $e->getMessage());

            return 1;
        }

        return 0;
    }

    /**
     * Get the current Prestashop Db version
     *
     * @return string
     */
    protected function getCurrentVersion(): string
    {
        return \Configuration::get('PS_VERSION_DB');
    }

    /**
     * Check if ps version is Valid
     *
     * Only from the one where the console is available
     *
     * @param string $psVersion
     *
     * @return bool
     */
    protected function isvalidPsVersion(string $psVersion): bool
    {
        return preg_match(self::PS_VERSIONS_REGEXP, $psVersion);
    }
}
