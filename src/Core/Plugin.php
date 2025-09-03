<?php
/**
 * Main Plugin Class
 *
 * @package AISB\Core
 * @since 2.0.0
 */

namespace AISB\Core;

use AISB\Admin\AdminMenu;
use AISB\Admin\MetaBoxes;
use AISB\Frontend\TemplateLoader;
use AISB\Frontend\Assets as FrontendAssets;
use AISB\Admin\Assets as AdminAssets;
use AISB\Database\Migrations;
use AISB\Builders\BuilderDetector;

/**
 * Main plugin class using singleton pattern
 */
class Plugin {
    /**
     * Plugin instance
     *
     * @var Plugin|null
     */
    private static $instance = null;

    /**
     * Plugin version
     *
     * @var string
     */
    private $version = '2.0.0';

    /**
     * Plugin slug
     *
     * @var string
     */
    private $plugin_slug = 'ai-section-builder';

    /**
     * Service container
     *
     * @var Container
     */
    private $container;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct() {
        $this->define_constants();
        $this->init_container();
    }

    /**
     * Get plugin instance
     *
     * @return Plugin
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize the plugin
     */
    public function init() {
        // Load text domain
        add_action('plugins_loaded', [$this, 'load_textdomain']);
        
        // Initialize components
        add_action('init', [$this, 'init_components']);
        
        // Admin hooks
        if (is_admin()) {
            $this->init_admin();
        }
        
        // Frontend hooks
        if (!is_admin()) {
            $this->init_frontend();
        }
        
        // Common hooks
        $this->init_common();
    }

    /**
     * Define plugin constants
     */
    private function define_constants() {
        if (!defined('AISB_VERSION')) {
            define('AISB_VERSION', $this->version);
        }
        
        if (!defined('AISB_PLUGIN_FILE')) {
            define('AISB_PLUGIN_FILE', dirname(dirname(__DIR__)) . '/ai-section-builder.php');
        }
        
        if (!defined('AISB_PLUGIN_DIR')) {
            define('AISB_PLUGIN_DIR', plugin_dir_path(AISB_PLUGIN_FILE));
        }
        
        if (!defined('AISB_PLUGIN_URL')) {
            define('AISB_PLUGIN_URL', plugin_dir_url(AISB_PLUGIN_FILE));
        }
        
        if (!defined('AISB_PLUGIN_BASENAME')) {
            define('AISB_PLUGIN_BASENAME', plugin_basename(AISB_PLUGIN_FILE));
        }
    }

    /**
     * Initialize service container
     */
    private function init_container() {
        $this->container = Container::getInstance();
        
        // Register services
        $this->container->register('builder_detector', function() {
            return new BuilderDetector();
        });
        
        $this->container->register('meta_boxes', function() {
            return new MetaBoxes($this->container->get('builder_detector'));
        });
        
        $this->container->register('admin_menu', function() {
            return new AdminMenu();
        });
        
        $this->container->register('template_loader', function() {
            return new TemplateLoader($this->container->get('builder_detector'));
        });
        
        $this->container->register('migrations', function() {
            return new Migrations();
        });
        
        $this->container->register('admin_assets', function() {
            return new AdminAssets();
        });
        
        $this->container->register('frontend_assets', function() {
            return new FrontendAssets();
        });
    }

    /**
     * Initialize components
     */
    public function init_components() {
        // Run migrations
        $this->container->get('migrations')->run();
    }

    /**
     * Initialize admin components
     */
    private function init_admin() {
        // Admin menu
        $this->container->get('admin_menu')->init();
        
        // Meta boxes
        $this->container->get('meta_boxes')->init();
        
        // Admin assets
        $this->container->get('admin_assets')->init();
    }

    /**
     * Initialize frontend components
     */
    private function init_frontend() {
        // Template loader
        $this->container->get('template_loader')->init();
        
        // Frontend assets
        $this->container->get('frontend_assets')->init();
    }

    /**
     * Initialize common components
     */
    private function init_common() {
        // Builder detector is used in both admin and frontend
        $this->container->get('builder_detector')->init();
    }

    /**
     * Load plugin text domain
     */
    public function load_textdomain() {
        load_plugin_textdomain(
            'ai-section-builder',
            false,
            dirname(AISB_PLUGIN_BASENAME) . '/languages'
        );
    }

    /**
     * Get service from container
     *
     * @param string $service Service name
     * @return mixed
     */
    public function get($service) {
        return $this->container->get($service);
    }

    /**
     * Plugin activation
     */
    public static function activate() {
        $activator = new Activator();
        $activator->activate();
    }

    /**
     * Plugin deactivation
     */
    public static function deactivate() {
        $deactivator = new Deactivator();
        $deactivator->deactivate();
    }

    /**
     * Prevent cloning
     */
    private function __clone() {}

    /**
     * Prevent unserialization
     */
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
}