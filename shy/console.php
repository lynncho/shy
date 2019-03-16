<?php
/**
 * Shy Framework Console
 *
 * @author    lynn<admin@lynncho.cn>
 * @link      http://lynncho.cn/
 */

namespace shy;

use Exception;
use shy\core\pipeline;

class console
{
    protected $config = [];

    protected $command = '';

    protected $params = [];

    protected $class;

    protected $method;

    /**
     * console constructor.
     *
     */
    public function __construct()
    {
        $this->setting();
    }

    protected function setting()
    {
        /**
         * Time Zone
         */
        date_default_timezone_set(config('timezone'));

        if (config('illuminate_database')) {
            init_illuminate_database();
        }
    }

    public function getCommandList()
    {
        return array_keys($this->config);
    }

    public function exceptionNotice()
    {
        return <<<EOT

You can use `php console list` to get all command.

See the log for more error information. 
Log dir: cache/log/console/exception/
EOT;
    }

    /**
     * Run command
     *
     * @throws Exception
     */
    public function run()
    {
        $this->bootstrap();

        if (
            !class_exists($namespaceClass = 'app\\console\\' . $this->class)
            && !class_exists($namespaceClass = 'shy\\console\\command\\' . $this->class)
        ) {
            throw new Exception('class ' . $this->class . ' not found');
        }
        if (!method_exists($namespaceClass, $this->method)) {
            throw new Exception('method ' . $this->method . ' not found');
        }

        $result = shy('pipeline', new pipeline())
            ->send(...$this->params)
            ->through($namespaceClass)
            ->via($this->method)
            ->run();

        echo PHP_EOL . $result . PHP_EOL . PHP_EOL;
    }

    /**
     * Bootstrap
     *
     * @throws Exception
     */
    protected function bootstrap()
    {
        global $argv;
        array_shift($argv);
        $this->command = array_shift($argv);
        $this->params = $argv;
        $this->config = config_all('console');
        if (isset($this->config[$this->command])) {
            $route = $this->config[$this->command];
            $route = explode('@', $route);
            if (isset($route[0], $route[1])) {
                $this->class = $route[0];
                $this->method = $route[1];
            } else {
                throw new Exception('command ' . $this->command . ' config error');
            }
        } else {
            throw new Exception('command ' . $this->command . ' not find');
        }
    }

}
