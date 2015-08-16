<?php

spl_autoload_register(function($path){
    $newPath = preg_replace('~odango\\\\~i', '', $path);
    $newPath = preg_replace('~\\\\~i', '/', $newPath);
    if (file_exists($newPath.'.php')) {
        include_once($newPath.'.php');
        return true;
    }

    return false;
});

var_dump((new Odango\NyaaCollector)->collectRecursive('Log Horizon', ['category'=> '1_37']));
