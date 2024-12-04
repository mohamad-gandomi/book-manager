<?php
/**
 * Plugin Name:     Book Manager
 * Plugin URI:      https://example.com
 * Plugin Prefix:   BM
 * Description:     A plugin to manage books using the Rabbit framework.
 * Author:          Mohamad Gandomi
 * Author URI:      https://example.me
 * Text Domain:     book-manager
 * Domain Path:     /languages
 * Version:         1.0.0
 */

namespace RabbitBookManager;

use Rabbit\Application;
use Rabbit\Redirects\RedirectServiceProvider;
use Rabbit\Database\DatabaseServiceProvider;
use Rabbit\Logger\LoggerServiceProvider;
use Rabbit\Plugin;
use Rabbit\Redirects\AdminNotice;
use Rabbit\Templates\TemplatesServiceProvider;
use Rabbit\Utils\Singleton;
use League\Container\Container;
use RabbitBookManager\Book\BookServiceProvider;

if (file_exists(dirname(__FILE__) . '/vendor/autoload.php')) {
    require dirname(__FILE__) . '/vendor/autoload.php';
}

/**
 * Class RabbitBookManager
 * @package RabbitBookManager
 */
class RabbitBookManager extends Singleton
{
    /**
     * @var Container
     */
    private $application;

    /**
     * WPSmsWooPro constructor.
     */
    public function __construct()
    {
        $this->application = Application::get()->loadPlugin(__DIR__, __FILE__, 'config');
        $this->init();
    }

    public function init()
    {
        try {

            /**
             * Load service providers
             */
            $this->application->addServiceProvider(RedirectServiceProvider::class);
            $this->application->addServiceProvider(DatabaseServiceProvider::class);
            $this->application->addServiceProvider(TemplatesServiceProvider::class);
            $this->application->addServiceProvider(LoggerServiceProvider::class);
            // Load your own service providers here...
            $this->application->addServiceProvider(BookServiceProvider::class);

            /**
             * Activation hooks
             */
            $this->application->onActivation(function () {
                global $wpdb;
                $table_name = $wpdb->prefix . 'books_info';

                $charset_collate = $wpdb->get_charset_collate();
                $sql = "CREATE TABLE $table_name (
                    ID BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    post_id BIGINT UNSIGNED NOT NULL,
                    isbn VARCHAR(13) NOT NULL,
                    UNIQUE KEY isbn (isbn)
                ) $charset_collate;";

                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                dbDelta($sql);
            });

            /**
             * Deactivation hooks
             */
            $this->application->onDeactivation(function () {
                // Clear events, cache or something else
            });

            $this->application->boot(function (Plugin $plugin) {
                $plugin->loadPluginTextDomain();
            });

        } catch (Exception $e) {
            /**
             * Print the exception message to admin notice area
             */
            add_action('admin_notices', function () use ($e) {
                AdminNotice::permanent(['type' => 'error', 'message' => $e->getMessage()]);
            });

            /**
             * Log the exception to file
             */
            add_action('init', function () use ($e) {
                if ($this->application->has('logger')) {
                    $this->application->get('logger')->warning($e->getMessage());
                }
            });
        }
    }

    /**
     * @return Container
     */
    public function getApplication()
    {
        return $this->application;
    }
}

/**
 * Returns the main instance of RabbitBookManager.
 * @return RabbitBookManager
 */
function RabbitBookManager()
{
    return RabbitBookManager::get();
}

RabbitBookManager();

