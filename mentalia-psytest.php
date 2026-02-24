<?php
/**
 * Plugin Name: Mentalia PsyTest
 * Plugin URI: https://mentalia.id
 * Description: Plugin tes psikologi untuk website Mentalia. Kelola tes, soal, dan hasil psikologi langsung dari dashboard WordPress.
 * Version: 1.0.0
 * Author: Mentalia Team
 * Author URI: https://mentalia.id
 * Text Domain: mentalia-psytest
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Update URI: https://github.com/praseetyaa/mentalia-psytest
 * GitHub Plugin URI: praseetyaa/mentalia-psytest
 */

if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('MENTALIA_PSYTEST_VERSION', '1.0.0');
define('MENTALIA_PSYTEST_FILE', __FILE__);
define('MENTALIA_PSYTEST_PATH', plugin_dir_path(__FILE__));
define('MENTALIA_PSYTEST_URL', plugin_dir_url(__FILE__));
define('MENTALIA_PSYTEST_BASENAME', plugin_basename(__FILE__));

/**
 * Main Plugin Class
 */
final class Mentalia_PsyTest
{

    private static $instance = null;

    public static function instance()
    {
        if (is_null(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->includes();
        $this->init_hooks();
    }

    private function includes()
    {
        require_once MENTALIA_PSYTEST_PATH . 'includes/class-database.php';
        require_once MENTALIA_PSYTEST_PATH . 'includes/class-scoring.php';
        require_once MENTALIA_PSYTEST_PATH . 'includes/class-updater.php';

        if (is_admin()) {
            require_once MENTALIA_PSYTEST_PATH . 'admin/class-admin.php';
        }

        require_once MENTALIA_PSYTEST_PATH . 'public/class-public.php';
    }

    private function init_hooks()
    {
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);

        add_action('init', [$this, 'init']);
        add_action('plugins_loaded', [$this, 'on_plugins_loaded']);
    }

    public function activate()
    {
        $database = new Mentalia_PsyTest_Database();
        $database->create_tables();
        flush_rewrite_rules();
    }

    public function deactivate()
    {
        flush_rewrite_rules();
    }

    public function init()
    {
        // Load text domain
        load_plugin_textdomain('mentalia-psytest', false, dirname(MENTALIA_PSYTEST_BASENAME) . '/languages');
    }

    public function on_plugins_loaded()
    {
        // Initialize admin
        if (is_admin()) {
            new Mentalia_PsyTest_Admin();
        }

        // Initialize public
        new Mentalia_PsyTest_Public();

        // Register Elementor widget
        if (did_action('elementor/loaded')) {
            add_action('elementor/widgets/register', [$this, 'register_elementor_widget']);
        }

        // Initialize GitHub updater
        if (is_admin()) {
            new Mentalia_PsyTest_Updater(MENTALIA_PSYTEST_FILE);
        }
    }

    public function register_elementor_widget($widgets_manager)
    {
        require_once MENTALIA_PSYTEST_PATH . 'includes/class-elementor-widget.php';
        $widgets_manager->register(new Mentalia_PsyTest_Elementor_Widget());
    }
}

/**
 * Returns the main plugin instance
 */
function mentalia_psytest()
{
    return Mentalia_PsyTest::instance();
}

// Initialize
mentalia_psytest();
