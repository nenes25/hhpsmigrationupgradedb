<?php

namespace Hhennes\PsMigrationUpgradeDb\Tests\Unit\DbUpgrader;

use Hhennes\PsMigrationUpgradeDb\DbUpgrader\Upgrader;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UpgraderTest extends TestCase
{
    private $logger;
    private $upgrader;
    private $pathToUpgradeScripts;
    private $sqlDir;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        // Mock UpgradeException class if not already defined
        if (!class_exists('PrestaShop\Module\AutoUpgrade\UpgradeException')) {
            eval('namespace PrestaShop\Module\AutoUpgrade; class UpgradeException extends \Exception {}');
        }

        // Mock Db class if not already defined
        if (!class_exists('Db')) {
            eval('class Db {
                public static function getInstance() { return new self(); }
                public function execute($sql, $use_cache = true) { return true; }
                public function getMsgError() { return "test error"; }
                public function getNumberError() { return "1234"; }
            }');
        }

        // Define PrestaShop constants if not already defined
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

        // Create a dummy upgrade directory and sql files
        $this->pathToUpgradeScripts = _PS_MODULE_DIR_ . 'autoupgrade/upgrade';
        $this->sqlDir = $this->pathToUpgradeScripts . '/sql/';
        if (!is_dir($this->sqlDir)) {
            mkdir($this->sqlDir, 0777, true);
        }
        file_put_contents($this->sqlDir . '1.7.8.0.sql', 'ALTER TABLE PREFIX_customer ADD new_column VARCHAR(255);');
        file_put_contents($this->sqlDir . '1.7.8.1.sql', 'UPDATE PREFIX_configuration SET value = 1 WHERE name = \'PS_SHOP_ENABLE\';');
        file_put_contents($this->sqlDir . '8.0.0.sql', 'SELECT * FROM PREFIX_product;');

        $this->upgrader = new Upgrader($this->logger);
    }

    protected function tearDown(): void
    {
        // Clean up dummy files and directories
        unlink($this->sqlDir . '1.7.8.0.sql');
        unlink($this->sqlDir . '1.7.8.1.sql');
        unlink($this->sqlDir . '8.0.0.sql');
        rmdir($this->sqlDir);
        rmdir($this->pathToUpgradeScripts);
    }

    public function testItCanBeInstantiated()
    {
        $this->assertInstanceOf(Upgrader::class, $this->upgrader);
    }

    public function testGetUpgradeSqlFilesListToApply()
    {
        $this->upgrader->setOldVersion('1.7.7.0');
        $this->upgrader->setDestinationVersion('1.7.8.1');

        $upgraderTest = new class($this->logger) extends Upgrader {
            public function __construct(LoggerInterface $logger)
            {
                parent::__construct($logger);
                // We need to override the path to the upgrade scripts for the test
                $this->pathToUpgradeScripts = sys_get_temp_dir() . '/prestashop/modules/autoupgrade/upgrade';
            }

            public function getUpgradeSqlFilesListToApply(string $upgrade_dir_sql, string $oldversion): array
            {
                return parent::getUpgradeSqlFilesListToApply($upgrade_dir_sql, $oldversion);
            }
        };

        $upgraderTest->setOldVersion('1.7.7.0');
        $upgraderTest->setDestinationVersion('1.7.8.1');

        $files = $upgraderTest->getUpgradeSqlFilesListToApply($this->sqlDir, '1.7.7.0');

        $this->assertCount(2, $files);
        $this->assertArrayHasKey('1.7.8.0', $files);
        $this->assertArrayHasKey('1.7.8.1', $files);
        $this->assertArrayNotHasKey('8.0.0', $files);
    }

    public function testApplySqlParams()
    {
        $sqlFiles = [
            '1.7.8.0' => $this->sqlDir . '1.7.8.0.sql',
        ];

        $upgraderTest = new class($this->logger) extends Upgrader {
            public function applySqlParams(array $sqlFiles): array
            {
                return parent::applySqlParams($sqlFiles);
            }
        };

        $requests = $upgraderTest->applySqlParams($sqlFiles);

        $this->assertCount(1, $requests);
        $this->assertArrayHasKey('1.7.8.0', $requests);
        $this->assertEquals('ALTER TABLE ps_customer ADD new_column VARCHAR(255)', trim($requests['1.7.8.0'][0]));
    }

    public function testUpgradeDbDryRun()
    {
        $this->upgrader->setOldVersion('1.7.7.0');
        $this->upgrader->setDestinationVersion('1.7.8.1');
        $this->upgrader->setRunMode(Upgrader::RUN_MODE_DRY_RUN);

        $this->logger->expects($this->exactly(2))
            ->method('warning')
            ->with($this->stringContains('Apply upgrade file'));

        $this->upgrader->upgradeDb();
    }

    public function testSetAndGetOldVersion()
    {
        $version = '1.7.5.0';
        $result = $this->upgrader->setOldVersion($version);

        $this->assertInstanceOf(Upgrader::class, $result);
        $this->assertEquals($version, $this->upgrader->getOldVersion());
    }

    public function testSetAndGetDestinationVersion()
    {
        $version = '1.7.8.0';
        $result = $this->upgrader->setDestinationVersion($version);

        $this->assertInstanceOf(Upgrader::class, $result);
        $this->assertEquals($version, $this->upgrader->getDestinationVersion());
    }

    public function testSetAndGetRunMode()
    {
        $this->assertEquals(Upgrader::RUN_MODE_STANDARD, $this->upgrader->getRunMode());

        $result = $this->upgrader->setRunMode(Upgrader::RUN_MODE_DRY_RUN);
        $this->assertInstanceOf(Upgrader::class, $result);
        $this->assertEquals(Upgrader::RUN_MODE_DRY_RUN, $this->upgrader->getRunMode());
    }

    public function testRunModeConstants()
    {
        $this->assertEquals('standard', Upgrader::RUN_MODE_STANDARD);
        $this->assertEquals('dry-run', Upgrader::RUN_MODE_DRY_RUN);
    }

    public function testGetUpgradeSqlFilesListThrowsExceptionWhenDirectoryNotExists()
    {
        $this->expectException(\PrestaShop\Module\AutoUpgrade\UpgradeException::class);
        $this->expectExceptionMessage('Unable to find upgrade directory in the installation path.');

        $upgraderTest = new class($this->logger) extends Upgrader {
            public function getUpgradeSqlFilesListToApply(string $upgrade_dir_sql, string $oldversion): array
            {
                return parent::getUpgradeSqlFilesListToApply($upgrade_dir_sql, $oldversion);
            }
        };

        $upgraderTest->setDestinationVersion('1.7.8.0');
        $upgraderTest->getUpgradeSqlFilesListToApply('/non/existent/path/', '1.7.7.0');
    }

    public function testGetUpgradeSqlFilesListFiltersCorrectly()
    {
        $upgraderTest = new class($this->logger) extends Upgrader {
            public function __construct(LoggerInterface $logger)
            {
                parent::__construct($logger);
                $this->pathToUpgradeScripts = sys_get_temp_dir() . '/prestashop/modules/autoupgrade/upgrade';
            }

            public function getUpgradeSqlFilesListToApply(string $upgrade_dir_sql, string $oldversion): array
            {
                return parent::getUpgradeSqlFilesListToApply($upgrade_dir_sql, $oldversion);
            }
        };

        $upgraderTest->setOldVersion('1.7.8.0');
        $upgraderTest->setDestinationVersion('1.7.8.1');

        $files = $upgraderTest->getUpgradeSqlFilesListToApply($this->sqlDir, '1.7.8.0');

        $this->assertCount(1, $files);
        $this->assertArrayHasKey('1.7.8.1', $files);
        $this->assertArrayNotHasKey('1.7.8.0', $files);
        $this->assertArrayNotHasKey('8.0.0', $files);
    }

    public function testApplySqlParamsReplacesPlaceholders()
    {
        $sqlFiles = [
            '1.7.8.0' => $this->sqlDir . '1.7.8.0.sql',
        ];

        $upgraderTest = new class($this->logger) extends Upgrader {
            public function applySqlParams(array $sqlFiles): array
            {
                return parent::applySqlParams($sqlFiles);
            }
        };

        $requests = $upgraderTest->applySqlParams($sqlFiles);

        $this->assertCount(1, $requests);
        $this->assertIsArray($requests['1.7.8.0']);
        // Check that PREFIX_ was replaced with _DB_PREFIX_
        $this->assertStringNotContainsString('PREFIX_', $requests['1.7.8.0'][0]);
        $this->assertStringContainsString('ps_', $requests['1.7.8.0'][0]);
    }

    public function testUpgradeDbInStandardMode()
    {
        $this->upgrader->setOldVersion('1.7.7.0');
        $this->upgrader->setDestinationVersion('1.7.8.1');
        $this->upgrader->setRunMode(Upgrader::RUN_MODE_STANDARD);

        $this->logger->expects($this->atLeast(2))
            ->method('warning')
            ->with($this->stringContains('Apply upgrade file'));

        $this->upgrader->upgradeDb();
    }

    public function testFluentInterface()
    {
        $result = $this->upgrader
            ->setOldVersion('1.7.7.0')
            ->setDestinationVersion('1.7.8.0')
            ->setRunMode(Upgrader::RUN_MODE_DRY_RUN);

        $this->assertInstanceOf(Upgrader::class, $result);
        $this->assertEquals('1.7.7.0', $this->upgrader->getOldVersion());
        $this->assertEquals('1.7.8.0', $this->upgrader->getDestinationVersion());
        $this->assertEquals(Upgrader::RUN_MODE_DRY_RUN, $this->upgrader->getRunMode());
    }
}
