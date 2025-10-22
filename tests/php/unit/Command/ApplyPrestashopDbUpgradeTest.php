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

namespace Hhennes\PsMigrationUpgradeDb\Tests\Unit\Command;

use Hhennes\PsMigrationUpgradeDb\Command\ApplyPrestashopDbUpgrade;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class ApplyPrestashopDbUpgradeTest extends TestCase
{
    private $command;
    private $commandTester;

    protected function setUp(): void
    {
        // Mock PrestaShop Module class
        if (!class_exists('Module')) {
            eval('class Module {
                public static function getInstanceByName($name) { return new self(); }
                public static function isInstalled($name) { return true; }
            }');
        }

        // Mock PrestaShop Configuration class
        if (!class_exists('Configuration')) {
            eval('class Configuration {
                public static function get($key) { return "1.7.8.0"; }
                public static function updateValue($key, $value) { return true; }
            }');
        }

        // Mock Db class
        if (!class_exists('Db')) {
            eval('class Db {
                public static function getInstance() { return new self(); }
                public function execute($sql, $use_cache = true) { return true; }
                public function getMsgError() { return "test error"; }
                public function getNumberError() { return "1234"; }
            }');
        }

        // Define PrestaShop constants
        if (!defined('_PS_MODULE_DIR_')) {
            define('_PS_MODULE_DIR_', sys_get_temp_dir() . '/prestashop/modules/');
        }
        if (!defined('_DB_PREFIX_')) {
            define('_DB_PREFIX_', 'ps_');
        }
        if (!defined('_MYSQL_ENGINE_')) {
            define('_MYSQL_ENGINE_', 'InnoDB');
        }
        if (!defined('_DB_NAME_')) {
            define('_DB_NAME_', 'prestashop');
        }

        // Create test upgrade directory
        $sqlDir = _PS_MODULE_DIR_ . 'autoupgrade/upgrade/sql/';
        if (!is_dir($sqlDir)) {
            mkdir($sqlDir, 0777, true);
        }
        file_put_contents($sqlDir . '1.7.8.0.sql', 'SELECT 1;');
        file_put_contents($sqlDir . '1.7.8.1.sql', 'SELECT 2;');

        $application = new Application();
        $this->command = new ApplyPrestashopDbUpgrade();
        $application->add($this->command);

        $this->commandTester = new CommandTester($this->command);
    }

    protected function tearDown(): void
    {
        // Clean up
        $sqlDir = _PS_MODULE_DIR_ . 'autoupgrade/upgrade/sql/';
        if (file_exists($sqlDir . '1.7.8.0.sql')) {
            unlink($sqlDir . '1.7.8.0.sql');
        }
        if (file_exists($sqlDir . '1.7.8.1.sql')) {
            unlink($sqlDir . '1.7.8.1.sql');
        }
        if (is_dir($sqlDir)) {
            rmdir($sqlDir);
        }
        $upgradeDir = _PS_MODULE_DIR_ . 'autoupgrade/upgrade/';
        if (is_dir($upgradeDir)) {
            rmdir($upgradeDir);
        }
        $autoUpgradeDir = _PS_MODULE_DIR_ . 'autoupgrade/';
        if (is_dir($autoUpgradeDir)) {
            rmdir($autoUpgradeDir);
        }
    }

    public function testCommandIsConfiguredCorrectly()
    {
        $this->assertEquals('hhennes:psmigration:upgrade-db', $this->command->getName());
        $this->assertStringContainsString('Apply db upgrade', $this->command->getDescription());
    }

    public function testCommandHasRequiredArguments()
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasArgument('from-version'));
        $this->assertTrue($definition->hasArgument('to-version'));

        $fromVersionArg = $definition->getArgument('from-version');
        $toVersionArg = $definition->getArgument('to-version');

        $this->assertTrue($fromVersionArg->isRequired());
        $this->assertTrue($toVersionArg->isRequired());
    }

    public function testCommandHasExpectedOptions()
    {
        $definition = $this->command->getDefinition();

        $this->assertTrue($definition->hasOption('get-version'));
        $this->assertTrue($definition->hasOption('dry-run'));
        $this->assertTrue($definition->hasOption('no-db-config-update'));
    }

    public function testGetVersionOption()
    {
        $this->commandTester->execute([
            'from-version' => '1.7.7.0',
            'to-version' => '1.7.8.0',
            '--get-version' => true,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Current Db installed version', $output);
        $this->assertStringContainsString('1.7.8.0', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testInvalidFromVersionReturnsError()
    {
        $this->commandTester->execute([
            'from-version' => '1.6.0.0',
            'to-version' => '1.7.8.0',
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Please enter valid from and to versions', $output);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    public function testInvalidToVersionReturnsError()
    {
        $this->commandTester->execute([
            'from-version' => '1.7.7.0',
            'to-version' => '1.6.0.0',
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Please enter valid from and to versions', $output);
        $this->assertEquals(1, $this->commandTester->getStatusCode());
    }

    public function testValidVersionsWithDryRun()
    {
        $this->commandTester->execute([
            'from-version' => '1.7.7.0',
            'to-version' => '1.7.8.1',
            '--dry-run' => true,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Upgrade process start', $output);
        $this->assertStringContainsString('Dry run mode', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testValidVersionsWithUpgrade()
    {
        $this->commandTester->execute([
            'from-version' => '1.7.7.0',
            'to-version' => '1.7.8.1',
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('Upgrade process start', $output);
        $this->assertStringContainsString('applied with success', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testNoDbConfigUpdateOption()
    {
        $this->commandTester->execute([
            'from-version' => '1.7.7.0',
            'to-version' => '1.7.8.1',
            '--no-db-config-update' => true,
        ]);

        $output = $this->commandTester->getDisplay();
        $this->assertStringContainsString('applied with success', $output);
        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testValidPs17Versions()
    {
        $this->commandTester->execute([
            'from-version' => '1.7.5.0',
            'to-version' => '1.7.8.9',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }

    public function testValidPs80Versions()
    {
        $this->commandTester->execute([
            'from-version' => '8.0.0',
            'to-version' => '8.1.5',
        ]);

        $this->assertEquals(0, $this->commandTester->getStatusCode());
    }
}
