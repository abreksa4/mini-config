<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 2015-11-08
 * Time: 16:10
 */

namespace Mini\Config;

use ArrayAccess;
use Closure;

/**
 * Creates an array from the config files in the provided directories. Handles XML, PHP (return array), INI, and JSON
 * out of the box.
 *
 * Add directories and files to be included by calling <cide>Config->addTarget($string)</code> or
 * <code>Config->addTargets($array)</code>
 *
 * You can register your own extensions and their handlers, by calling
 * <code>Config->registerHandler($extension, $handler)</code> where
 * $extension is the file extension and $handler is a function which takes the file path as a parameter and must return
 * an array.
 *
 * Setting a handler with the extension of an existing handler will overwrite the exising handler with the new one.
 *
 * You can access the config values by treating Config as an array, i.e.:
 *
 * <code>
 * $config['cat1']['key1']
 * </code>
 *
 * One important note is that duplicate key values don't overwrite, they append values. So:
 * <code>
 * [
 *  'cat1' => [
 *      'key1' => 'value1',
 *  ]
 * ]
 * </code>
 * and
 * <code>
 * [cat1]
 * key1=value2
 * </code>
 * results in:
 * <code>
 * [
 *  'cat1' => [
 *      'key1' => [
 *          'value1',
 *          'value2',
 *      ]
 *  ]
 * ]
 * </code>
 *
 * @package Mini\Config
 */
class Config implements ArrayAccess {

    /**
     * The config array, holds the config from all merged config files.
     *
     * @var array
     */
    protected $config;

    /**
     * The array of targets for Config to include in the the config array.
     *
     * @var array
     */
    protected $directories = [];

    /**
     * The array of files for Config to include in the the config array.
     *
     * @var array
     */
    protected $files = [];

    /**
     * The array of handlers, as 'extension' => 'Closure'.
     *
     * @var array
     */
    protected $handlers;

    /**
     * Config constructor.
     *
     * You may pass a boolean true/false if the config data should be used as the parent key for all data in the file.
     * You may also pass an array of target paths to this constructor to add them to the array to include.
     *
     * @param array|null $targets
     * @internal param bool $groupByFile
     */
    public function __construct($targets = null) {
        if ($targets != null) {
            if (is_array($targets)) {
                foreach ($targets as $path) {
                    $this->addTarget($path);
                }
            } else {
                $this->addTarget($targets);
            }
        }
        $this->registerDefaultHandelers();
        $this->refresh();
    }

    /**
     * Adds a target to the target listing to include.
     *
     * @param string|array $target
     */
    public function addTarget($target) {
        if (is_array($target)) {
            foreach ($target as $p) {
                $this->addTarget($p);
            }
            return;
        }
        if (is_dir($target)) {
            $this->directories[] = rtrim($target, '/');
        } elseif (is_file($target)) {
            $this->files[] = $target;
        }
    }

    /**
     * Register the default handlers, 'php', 'xml', 'ini', and 'json'.
     */
    private function registerDefaultHandelers() {
        $this->registerHandler('xml', function ($file) {
            $xml2array = function ($xmlObject, $out = array()) use (&$xml2array) {
                foreach ((array)$xmlObject as $index => $node) {
                    $out[$index] = (is_object($node)) ? $xml2array($node) : $node;
                }
                return $out;
            };
            return $xml2array(simplexml_load_string(file_get_contents($file)));
        });
        $this->registerHandler('php', function ($file) {
            return include($file);
        });
        $this->registerHandler('ini', function ($file) {
            return parse_ini_file($file, true);
        });
        $this->registerHandler('json', function ($file) {
            return json_decode(file_get_contents($file), true);
        });
    }

    /**
     *
     *
     * @param string $extension
     * @param Closure $handler
     */
    public function registerHandler($extension, $handler) {
        $this->handlers[$extension] = $handler;
    }

    /**
     * Build the config array, call this after adding config directories after the constructor.
     */
    public function refresh() {
        $this->config = [];
        foreach ($this->directories as $dir) {
            foreach ($this->handlers as $handler => $function) {
                foreach (glob($dir . "/*." . $handler, GLOB_NOSORT) as $file) {
                    $this->config = array_merge_recursive($this->config, $function($file));
                }
            }
        }
        foreach ($this->files as $file) {
            $pathinfo = pathinfo($file);
            if (isset($pathinfo['extension'])) {
                $ext = $pathinfo['extension'];
                if (in_array($ext, array_keys($this->handlers))) {
                    $this->config = array_merge_recursive($this->config, $this->handlers[$ext]($file));
                }
            }
        }
    }

    /**
     * Removes a handler from the internal handler array, stopping the parsing of files with that extension.
     *
     * @param string $extension
     */
    public function removeHandler($extension) {
        unset($this->handlers[$extension]);
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset) {
        return $this->__isset($offset);
    }

    /**
     * @param $key
     * @return bool
     */
    public function __isset($key) {
        return isset($this->config[$key]);
    }

    /**
     * @param mixed $offset
     * @return null
     */
    public function offsetGet($offset) {
        return $this->__get($offset);
    }

    /**
     * @param $key
     * @return null
     */
    public function __get($key) {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        } else {
            return null;
        }
    }

    /**
     * @param $key
     * @param $value
     */
    public function __set($key, $value) {
        if ($key === null) {
            $this->config[] = $value;
        } else {
            $this->config[$key] = $value;
        }
    }

    /**
     * @param mixed $offset
     * @param mixed $data
     */
    public function offsetSet($offset, $data) {
        $this->__set($offset, $data);
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset) {
        unset($this->config[$offset]);
    }

    /**
     * @param $name
     */
    public function __unset($name) {
        if (isset($this->config[$name])) {
            unset($this->config[$name]);
        }
    }

}
