<?php

namespace Odango;

class Registry {
    private static $stash;
    private static $database;

    public static function setStash($stash)
    {
        self::$stash = $stash;
    }

    public static function getStash()
    {
        return self::$stash;
    }

    public static function setDatabase($database)
    {
        self::$database = $database;
    }

    public static function getDatabase()
    {
        return self::$database;
    }
}
