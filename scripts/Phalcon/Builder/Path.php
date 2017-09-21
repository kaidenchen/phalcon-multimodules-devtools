<?php

/*
  +------------------------------------------------------------------------+
  | Phalcon Developer Tools                                                |
  +------------------------------------------------------------------------+
  | Copyright (c) 2011-2016 Phalcon Team (http://www.phalconphp.com)       |
  +------------------------------------------------------------------------+
  | This source file is subject to the New BSD License that is bundled     |
  | with this package in the file docs/LICENSE.txt.                        |
  |                                                                        |
  | If you did not receive a copy of the license and are unable to         |
  | obtain it through the world-wide-web, please send an email             |
  | to license@phalconphp.com so we can send you a copy immediately.       |
  +------------------------------------------------------------------------+
  | Authors: Serghei Iakovlev <serghei@phalconphp.com>                     |
  +------------------------------------------------------------------------+
*/

namespace Phalcon\Builder;

use Phalcon\Config;
use Phalcon\Config\Adapter\Ini as ConfigIni;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

/**
 * Path Class
 *
 * @package Phalcon\Builder
 */
class Path
{
    protected $rootPath = null;

    public function __construct($rootPath = null)
    {
        $this->rootPath = $rootPath ?: realpath('.') . DIRECTORY_SEPARATOR;
    }

    /**
     * Tries to find the current configuration in the application
     *
     * @param string $type Config type: ini | php
     * @return \Phalcon\Config
     * @throws BuilderException
     */
    public function getConfig($type = null)
    {
        $types = ['php' => true, 'ini' => true];
        $type  = isset($types[$type]) ? $type : 'ini';

        $configDirs = [ 'config/', 'config/'.ENVIRONMENT .'/' ];
        $configFiles = [ 'config', 'databases' ];

        foreach ($configDirs as $configPath) {
            if ( 'ini' == $type ) {
                foreach($configFiles as $key=>$val) {
                    $iniConfig = $this->rootPath . $configPath .$val .'.ini';
                    if(file_exists($iniConfig)) {
                        $tmpConfig = new ConfigIni($iniConfig);
                        if ( is_object($config) ) {
                            $config->merge($tmpConfig);
                        } else {
                            $config = $tmpConfig;
                        }
                    }
                }
            }
            foreach($configFiles as $k=>$v) {
                $phpConfig = $this->rootPath . $configPath .$v.'.php';
                if(file_exists($phpConfig)) {
                    $tmpConfig = include($phpConfig);
                    if ( isset($config) && is_object($config) ) {
                        $config->merge($tmpConfig);
                    } else {
                        $config = $tmpConfig;
                    }
                }
            }
        }
        if ( isset($config) && is_object($config) ) {
            return $config;
        }

        $directory = new RecursiveDirectoryIterator('.');
        $iterator = new RecursiveIteratorIterator($directory);
        foreach ($iterator as $f) {
            if (preg_match('/config\.php$/i', $f->getPathName())) {
                $config = include $f->getPathName();
                if (is_array($config)) {
                    $config = new Config($config);
                }

                return $config;
            } else {
                if (preg_match('/config\.ini$/i', $f->getPathName())) {
                    return new ConfigIni($f->getPathName());
                }
            }
        }

        throw new BuilderException("Builder can't locate the configuration file");
    }

    public function setRootPath($path)
    {
        $this->rootPath = rtrim($path, '\\/') . DIRECTORY_SEPARATOR;

        return $this;
    }

    public function getRootPath($path = null)
    {
        return $this->rootPath . ($path ? trim($path, '\\/') . DIRECTORY_SEPARATOR : '');
    }

    public function appendRootPath($pathPath)
    {
        $this->setRootPath($this->getRootPath() . rtrim($pathPath, '\\/') . DIRECTORY_SEPARATOR);
    }


    /**
     * Check if a path is absolute
     *
     * @param string $path Path to check
     *
     * @return bool
     */
    public function isAbsolutePath($path)
    {
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            if (preg_match('/^[A-Z]:\\\\/', $path)) {
                return true;
            }
        } else {
            if (substr($path, 0, 1) == DIRECTORY_SEPARATOR) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check Phalcon system dir
     *
     * @return bool
     */
    public function hasPhalconDir()
    {
        return file_exists($this->rootPath . '.phalcon');
    }
}
