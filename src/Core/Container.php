<?php
/**
 * Service Container
 *
 * @package AISB\Core
 * @since 2.0.0
 */

namespace AISB\Core;

/**
 * Simple service container for dependency injection
 */
class Container {
    /**
     * Container instance
     *
     * @var Container|null
     */
    private static $instance = null;

    /**
     * Registered services
     *
     * @var array
     */
    private $services = [];

    /**
     * Resolved service instances
     *
     * @var array
     */
    private $resolved = [];

    /**
     * Private constructor
     */
    private function __construct() {}

    /**
     * Get container instance
     *
     * @return Container
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Register a service
     *
     * @param string $name Service name
     * @param callable $resolver Service resolver callback
     */
    public function register($name, callable $resolver) {
        $this->services[$name] = $resolver;
    }

    /**
     * Get a service
     *
     * @param string $name Service name
     * @return mixed
     * @throws \Exception If service not found
     */
    public function get($name) {
        // Return already resolved instance
        if (isset($this->resolved[$name])) {
            return $this->resolved[$name];
        }

        // Resolve service
        if (isset($this->services[$name])) {
            $this->resolved[$name] = call_user_func($this->services[$name], $this);
            return $this->resolved[$name];
        }

        throw new \Exception("Service '{$name}' not found in container");
    }

    /**
     * Check if service exists
     *
     * @param string $name Service name
     * @return bool
     */
    public function has($name) {
        return isset($this->services[$name]);
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