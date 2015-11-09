<?php

namespace Mini\Config;

use ArrayAccess;
use Closure;

/**
 * Creates an array from the config files in the provided directories.
 *
 * @package Mini\Config
 * @author Andrew Breksa (abreksa4@gmail.com)
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
     * The array of handlers, as 'extension' => 'callable'.
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
     * Register a handler. Takes a file extension to match files by, and a function to process the file and return an array.
     *
     * @param string|array $extension
     * @param callable $handler
     */
    public function registerHandler($extension, $handler) {
        if (is_array($extension)) {
            foreach ($extension as $ext) {
                $this->handlers[$ext] = $handler;
            }
        } else {
            $this->handlers[$extension] = $handler;
        }
    }

    /**
     * Build the config array, call this after adding config directories after the constructor.
     */
    public function refresh() {
        $this->config = [];
        foreach ($this->directories as $dir) {
            foreach ($this->handlers as $ext => $function) {
                foreach (glob($dir . "/*." . $ext, GLOB_NOSORT) as $file) {
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
     * @return mixed
     */
    public function offsetGet($offset) {
        return $this->__get($offset);
    }

    /**
     * @param $key
     * @return mixed
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
