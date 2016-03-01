<?php

namespace Sledgehammer\Wordpress;

use Exception;
use Sledgehammer\Core\Database\Connection;
use Sledgehammer\Core\Debug\DebugR;
use Sledgehammer\Core\Debug\Logger;
use Sledgehammer\Core\Debug\ErrorHandler;
use Sledgehammer\Core\Object;
use Sledgehammer\Core\Url;
use Sledgehammer\Mvc\Template;
use Sledgehammer\Orm\Repository;

class Bridge extends Object {
    /**
     * Inspect the worpress database and
     */
    static function initialize()
    {
        static $initialized = false;
        if ($initialized) {
            return;
        }
        $initialized = true;
        if (function_exists('the_post') === false) { // Is wordpress not yet initialized?
            require_once(\Sledgehammer\PATH. 'web/wp-config.php');
        }
        // Lazy database connection
        Connection::$instances['wordpress'] = function () {
            if (defined('DB_USER') === false) {
                throw new Exception('No database configured');
            }
            $connection = new Connection('mysql://' . DB_USER . ':' . DB_PASSWORD . '@' . DB_HOST . '/' . DB_NAME);
            if (current(Logger::$instances) === $connection->logger && empty(Logger::$instances['Database'])) {
                unset(Logger::$instances[key(Logger::$instances)]);
                Logger::$instances['Database[wordpress]'] = $connection->logger;
            }
            return $connection;
        };
        if (empty(Connection::$instances['default'])) {
            Connection::$instances['default'] = 'wordpress';
        } 
        // Sledgehammer ORM configuration
        if (empty(Repository::$instances['default'])) {
            Repository::$instances['default'] = function () {
                $repo = new Repository();
                $repo->registerBackend(new WordpressRepositoryBackend());
                return $repo;
            };
        } else {
            $repo = Repository::instance();
            $repo->registerBackend(new WordpressRepositoryBackend());
        }

        if (defined('Sledgehammer\WEBPATH') === false) {
            $url = new Url(WP_HOME);
            $path = $url->path === '/' ? '/' : $url->path.'/';
            define('Sledgehammer\WEBPATH', $path);
            define('Sledgehammer\WEBROOT', $path);
        }
        define('WEBPATH', \Sledgehammer\WEBPATH);

        if (WP_DEBUG) {
            add_action('admin_enqueue_scripts',function (){
                wp_enqueue_style('sh-debug', '/../core/css/debug.css');
            });
            add_action('admin_footer', [self::class, 'statusbar']);
            add_action('wp_footer', [self::class, 'statusbar']);
            add_action( 'send_headers', function () {
                if (DebugR::isEnabled()) {
                    ob_start();
                    \Sledgehammer\statusbar();
                    DebugR::send('sledgehammer-statusbar', ob_get_clean(), true);
                }
            } );
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
                'identifier' => 'WPDB',
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