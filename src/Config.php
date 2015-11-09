<?php
/**
 * Created by PhpStorm.
 * User: Andrew
 * Date: 2015-11-08
 * Time: 16:10
 */

namespace Mini\Config;
use ArrayAccess;

/**
 * Creates an array from a the list of provided php (which return an array) and ini files.
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
     * The array of directories for Config to scan to build the config array.
     *
     * @var array
     */
    protected $dirs;


    /**
     * Config constructor.
     * You may pass a boolean true/false if the config data should be used as the parent key for all data in the file.
     * You may also pass an array of directory paths to this constructor to add them to the array to scan.
     * @param bool $groupByFile
     * @param array|null $directories
     */
    public function __construct($groupByFile = false, $directories = null) {
        if ($directories != null) {
            foreach ($directories as $path) {
                $this->addDirectory($path);
            }
        }
        $this->refresh();
    }

    /**
     * Adds a path to the directory listing to scan.
     *
     * @param string $path
     */
    public function addDirectory($path) {
        if (is_dir($path)) {
            $this->dirs[] = rtrim($path, '/');
        }
    }

    /**
     * Build the config array, call this after adding config directories after the constructor.
     */
    public function refresh() {
        if (empty($this->dirs)) {
            return;
        }
        $this->config = [];
        foreach ($this->dirs as $dir) {
            foreach (glob($dir . '/*.php') as $file) {
                $this->config = array_merge_recursive($this->config, include($file));
            }
            foreach (glob($dir . '/*.ini') as $file) {
                $this->config = array_merge_recursive($this->config, parse_ini_file($file, true));
            }
        }
    }

    /**
     * Add an array of directories to the scan list.
     *
     * @param array $dirs
     */
    public function addDirectories($dirs) {
        foreach ($dirs as $dir) {
            $this->addDirectory($dir);
        }
    }

    public function offsetExists($offset) {
        return $this->__isset($offset);
    }

    public function __isset($key) {
        return isset($this->config[$key]);
    }

    public function offsetGet($offset) {
        return $this->__get($offset);
    }

    public function __get($key) {
        if (isset($this->config[$key])) {
            return $this->config[$key];
        } else {
            return null;
        }
    }

    public function __set($key, $value) {
        if ($key === null) {
            $this->config[] = $value;
        } else {
            $this->config[$key] = $value;
        }
    }

    public function offsetSet($offset, $data) {
        $this->__set($offset, $data);
    }


    public function offsetUnset($offset) {
        unset($this->config[$offset]);
    }

    public function __unset($name) {
        if (isset($this->config[$name])) {
            unset($this->config[$name]);
        }
    }

}
