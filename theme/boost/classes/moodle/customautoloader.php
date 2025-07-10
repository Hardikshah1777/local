<?php

class customautoloader {

    /**
     * @var string
     */
    private $dir;

    const extension = '.php';

    public function __construct(string $dir) {
        $this->dir = $dir;
    }

    public function load(string $classname) {
        $filepath = $this->dir.DIRECTORY_SEPARATOR.str_replace('\\', DIRECTORY_SEPARATOR, $classname).self::extension;
        if (file_exists($filepath)) {
            require_once $filepath;
        }
    }
}

spl_autoload_register([new customautoloader(dirname(__FILE__)), 'load']);