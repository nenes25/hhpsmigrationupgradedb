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
if (!defined('_PS_VERSION_')) {
    exit;
}

class Hhpsmigrationupgradedb extends Module
{
    public function __construct()
    {
        $this->name = 'hhpsmigrationupgradedb';
        $this->tab = 'others';
        $this->version = '0.1.2';
        $this->author = 'hhennes';
        $this->bootstrap = true;
        $this->dependencies = ['autoupgrade'];
        $this->ps_versions_compliancy = ['min' => '1.7.5.0', 'max' => _PS_VERSION_];
        parent::__construct();

        $this->displayName = $this->l('Hh migration upgrade db');
        $this->description = $this->l('Simplify your db migration when upgrading prestashop');
    }
}
