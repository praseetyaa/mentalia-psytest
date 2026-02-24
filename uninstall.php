<?php
/**
 * Uninstall Mentalia PsyTest
 * Removes all plugin data when uninstalled
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

require_once plugin_dir_path(__FILE__) . 'includes/class-database.php';
Mentalia_PsyTest_Database::drop_tables();
