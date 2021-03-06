<?php

namespace Sledgehammer\Wordpress;

use Exception;
use Sledgehammer\Core\Database\Connection;
use Sledgehammer\Core\Debug\DebugR;
use Sledgehammer\Core\Debug\Logger;
use Sledgehammer\Core\Debug\ErrorHandler;
use Sledgehammer\Core\Base;
use Sledgehammer\Core\Url;
use Sledgehammer\Mvc\Component\Template;
use Sledgehammer\Orm\Repository;

class Bridge extends Base
{
    /**
     * Inspect the worpress database and.
     */
    public static function initialize()
    {
        static $initialized = false;
        if ($initialized) {
            return;
        }
        $initialized = true;
        if (function_exists('the_post') === false) {
            throw new Exception('Wordpress is not yet initialized');
        }
        // Lazy database connection
        Connection::$instances['wordpress'] = function () {
            if (defined('DB_USER') === false) {
                throw new Exception('No database configured');
            }
            $connection = new Connection('mysql://'.DB_USER.':'.DB_PASSWORD.'@'.DB_HOST.'/'.DB_NAME);
            if (empty(Logger::$instances['Database[wordpress]'])) {
                $index = array_search($connection->logger, Logger::$instances);
                unset(Logger::$instances[$index]);
                Logger::$instances['Database[wordpress]'] = $connection->logger;
            }

            return $connection;
        };
        if (empty(Connection::$instances['default'])) {
            Connection::$instances['default'] = 'wordpress';
        }
        // Sledgehammer ORM configuration
        Repository::configureDefault(function ($repo) {
            $repo->registerBackend(new WordpressRepositoryBackend());
        });

        if (defined('Sledgehammer\WEBPATH') === false) {
            if (defined('WP_HOME')) {
            $url = new Url(WP_HOME);
                $path = $url->path === '/' ? '/' : $url->path . '/';
            } else {
                $path = '/';
            }
            define('Sledgehammer\WEBPATH', $path);
            define('Sledgehammer\WEBROOT', $path);
        }
        define('WEBPATH', \Sledgehammer\WEBPATH);

        if (WP_DEBUG) {
            add_action('wp_enqueue_scripts', function () {
                wp_enqueue_style('sh-debug', '/core/css/debug.css');
            });
            add_action('admin_enqueue_scripts', function () {
                wp_enqueue_style('sh-debug', 'https://rawgit.com/sledgehammer/core/master/public/css/debug.css');
            });
            add_action('admin_footer', [self::class, 'statusbar']);
            add_action('wp_footer', [self::class, 'statusbar']);
            add_action('send_headers', function () {
                if (DebugR::isEnabled()) {
                    ob_start();
                    echo $_SERVER['REQUEST_METHOD'].' '.$_SERVER['REQUEST_URI'];
                    \Sledgehammer\statusbar();
                    DebugR::send('sledgehammer-statusbar', ob_get_clean(), true);
                }
            }, PHP_INT_MAX);
            add_filter('template_include', function ($template) {
                if (empty(ErrorHandler::$instances['default'])) {
                    ErrorHandler::enable();
                }
                if (defined('Sledgehammer\GENERATED') === false) {
                    define('Sledgehammer\GENERATED', microtime(true));
                }

                return $template;
            }, PHP_INT_MAX);
        }
    }

    public static function statusbar()
    {
        if (defined('SAVEQUERIES') && SAVEQUERIES) {
            $logger = new Logger([
                'identifier' => 'wpdb',
                'renderer' => [Connection::instance(), 'renderLog'],
                'plural' => 'queries',
                'singular' => 'query',
                'columns' => ['SQL', 'Duration'],
            ]);
            foreach ($GLOBALS['wpdb']->queries as $item) {
                $logger->append($item[0], ['duration' => $item[1]]);
            }
        }
        render(new Template('sledgehammer/mvc/templates/statusbar.php'));
    }
}
