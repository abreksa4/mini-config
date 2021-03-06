<?php

namespace Mini\Config;

use ArrayAccess;
use JsonSerializable;

/**
 * Creates an array from the config files in the provided directories.
 *
 * @package Mini\Config
 * @author Andrew Breksa (abreksa4@gmail.com)
 */
class Config implements ArrayAccess, JsonSerializable, \Serializable {

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
     * An array of startup options, currently supports the keys 'targets' which should contain an array of targets, and
     * 'handlers' an array of handlers in the format [$extension, $handler].
     *
     * @param array $options
     */
    public function __construct($options = null) {
        if (isset($options)) {
            if (isset($options['targets'])) {
                if (is_array($options['targets'])) {
                    foreach ($options['targets'] as $path) {
                        $this->addTarget($path);
                    }
                } else {
                    $this->addTarget($options['targets']);
                }
            }
            if (isset($options['handlers'])) {
                foreach (array_keys($options['handlers']) as $ext) {
                    $this->registerHandler($ext, $options['handlers'][$ext]);
                }
            }
        }
        $this->registerDefaultHandlers();
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
     * Register a handler. Takes a file extension to match files by, and a function to process the file and return an
     * array.
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
     * Register the default handlers, 'php', 'xml', 'ini', and 'json'.
     */
    private function registerDefaultHandlers() {
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
            $data = include($file);
            if ($data != null && is_array($data)) {
                return $data;
            }
            return;
        });
        $this->registerHandler('ini', function ($file) {
            return parse_ini_file($file, true);
        });
        $this->registerHandler('json', function ($file) {
            return json_decode(file_get_contents($file), true);
        });
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
     * Merges the provided array and config array into the config array
     *
     * @param array $config
     */
    public function merge(array $config) {
        $this->config = array_merge_recursive($this->config, $config);
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

    /**
     * String representation of object
     * @link http://php.net/manual/en/serializable.serialize.php
     * @return string the string representation of the object or null
     * @since 5.1.0
     */
    public function serialize() {
        return $this->jsonSerialize();
    }

    /**
     * Specify data which should be serialized to JSON
     * @link http://php.net/manual/en/jsonserializable.jsonserialize.php
     * @return mixed data which can be serialized by <b>json_encode</b>,
     * which is a value of any type other than a resource.
     * @since 5.4.0
     */
    function jsonSerialize() {
        return $this->config;
    }

    /**
     * Constructs the object
     * @link http://php.net/manual/en/serializable.unserialize.php
     * @param string $serialized <p>
     * The string representation of the object.
     * </p>
     * @return void
     * @since 5.1.0
     */
    public function unserialize($serialized) {
        $this->config = json_decode($serialized, true);
    }
}

/**
 * Utils class for Mini\Config
 * @package Mini\Config
 * @author Andrew Breksa (abreksa4@gmail.com)
 */
class Utils {
    /**
     * Merges two arrays, and returns the result. Takes an optional $overwrite parameter to overwrite string value with
     * those in the second array, i.e.:
     * <code>
     * $this->_merge([
     *     'database'=>[
     *         'password' => 'toor'
     *     ]],[
     *     'database'=>[
     *         'password' => 'password'
     * ]], true);
     * </code>
     *
     * Will result in:
     * <code>
     * [
     *     'database' => [
     *         'password' => 'password
     *     ]
     * ]
     * </code>
     *
     * @param array $array1
     * @param array $array2
     * @param bool|false $overwrite
     * @return array
     */
    public static function merge($array1, $array2, $overwrite = false) {
        $array = $array1;
        foreach (array_keys($array2) as $key) {
            if (!isset($array1[$key])) {
                $array[$key] = $array2[$key];
                continue;
            }
            if (is_array($array1[$key])) {
                $array[$key] = self::Merge($array1[$key], $array2[$key], $overwrite);
                continue;
            }
            if ($overwrite) {
                $array[$key] = $array2[$key];
            } else {
                $array[$key] = [$array[$key], $array2[$key]];
            }
        }
        return $array;
    }

}
