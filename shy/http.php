<?php
/**
 * Shy Framework Http
 *
 * @author    lynn<admin@lynncho.cn>
 * @link      http://lynncho.cn/
 */

namespace shy;

use shy\http\request;
use shy\http\router;
use shy\core\pipeline;
use shy\http\response;
use Smarty;
use Workerman\Protocols\Http as workerHttp;
use RuntimeException;

class http
{
    protected $lastCycleCount = 0;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->make();
        $this->setting();
    }

    /**
     * Bind Object
     */
    protected function make()
    {
        shy('request', new request());
        shy('router', new router());
        shy('pipeline', new pipeline());
        shy('response', new response());
    }

    /**
     * System Setting
     */
    protected function setting()
    {
        date_default_timezone_set(config('timezone'));

        defined('BASE_PATH') or define('BASE_PATH', config('base', 'path'));
        defined('APP_PATH') or define('APP_PATH', config('app', 'path'));
        defined('CACHE_PATH') or define('CACHE_PATH', config('cache', 'path'));
        defined('PUBLIC_PATH') or define('PUBLIC_PATH', config('public', 'path'));

        if (config('smarty')) {
            $this->smartySetting();
        }
        if (config('illuminate_database')) {
            $capsule = shy('capsule', 'Illuminate\Database\Capsule\Manager');
            $database = config('db', 'database');
            if (is_array($database)) {
                $capsule->setAsGlobal();
                foreach ($database as $name => $item) {
                    if (isset($item['driver'], $item['host'], $item['port'], $item['database'], $item['username'], $item['password'], $item['charset'], $item['collation'])) {
                        $capsule->addConnection([
                            'driver' => $item['driver'],
                            'host' => $item['host'],
                            'database' => $item['database'],
                            'username' => $item['username'],
                            'password' => $item['password'],
                            'charset' => $item['charset'],
                            'collation' => $item['collation'],
                            'prefix' => '',
                        ], $name);
                    } else {
                        throw new RuntimeException('Database config error.');
                    }
                }
            } else {
                throw new RuntimeException('Database config error.');
            }
        }
    }

    /**
     * Smarty Setting
     */
    protected function smartySetting()
    {
        $smarty = shy('smarty', new Smarty());
        $smarty->template_dir = config('app', 'path') . 'http' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;
        $smarty->compile_dir = config('cache', 'path') . 'app' . DIRECTORY_SEPARATOR . 'views' . DIRECTORY_SEPARATOR;

        $smartyConfig = config('smarty_config');
        if (isset($smartyConfig['left_delimiter']) && !empty($smartyConfig['left_delimiter'])) {
            $smarty->left_delimiter = $smartyConfig['left_delimiter'];
        }
        if (isset($smartyConfig['right_delimiter']) && !empty($smartyConfig['right_delimiter'])) {
            $smarty->right_delimiter = $smartyConfig['right_delimiter'];
        }
        if (isset($smartyConfig['caching']) && is_bool($smartyConfig['caching'])) {
            $smarty->caching = $smartyConfig['caching'];
        }
        if (isset($smartyConfig['cache_lifetime']) && is_int($smartyConfig['cache_lifetime'])) {
            $smarty->cache_lifetime = $smartyConfig['cache_lifetime'];
        }
        if (config('env') === 'development') {
            $smarty->debugging = true;
        }
    }

    /**
     * Session Start
     */
    protected function sessionStart()
    {
        if (IS_CLI) {
            global $_CYCLE_COUNT;
            if ($_CYCLE_COUNT > $this->lastCycleCount) {
                $this->lastCycleCount = $_CYCLE_COUNT;
                workerHttp::sessionStart();
            }
        } else {
            session_start();
        }
    }

    /**
     * Run
     */
    public function run()
    {
        $this->sessionStart();
        $request = shy('request');
        $request->init($_GET, $_POST, $_COOKIE, $_FILES, $_SERVER, file_get_contents('php://input'));
        if (empty(config('base_url'))) {
            defined('BASE_URL') or define('BASE_URL', $request->getBaseUrl());
        } else {
            defined('BASE_URL') or define('BASE_URL', config('base_url'));
        }
        logger('request/', serialize(shy('request')));

        /**
         * Run
         */
        $response = shy('pipeline')
            ->send($request)
            ->through('router')
            ->then(function ($response) {
                if (!empty($response)) {
                    shy('response')->send($response);
                }

                return $response;
            });

        $this->end($response);

        return $response;
    }

    /**
     * End
     *
     * @param $response
     */
    public function end($response)
    {
        /**
         * slow_log
         */
        if (config('slow_log')) {
            if (IS_CLI) {
                global $_SHY_START;
                $difference = microtime(true) - $_SHY_START;
                unset($_SHY_START);
            } else {
                $difference = microtime(true) - SHY_START;
            }

            if ($difference > config('slow_log_limit')) {
                logger('slowLog/log', json_encode([
                    'controller' => shy('router')->getController(),
                    'method' => shy('router')->getMethod(),
                    'difference' => $difference
                ]));
            }
        }

        logger('response/', serialize($response));
    }

}