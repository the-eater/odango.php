<?php

include '../vendor/autoload.php';

\Odango\Registry::setStash(new \Stash\Pool(new \Stash\Driver\Sqlite()));

$collector = new \Odango\NyaaCollector();
$collector->setNyaa(new \Odango\Nyaa\Database([ "dsn" => 'mysql:dbname=odango', 'username' => 'root', 'password' => null  ]));
$collector->setMatcher(\Odango\NyaaMatcher\Strict::construct());
var_dump(array_keys($collector->collectRecursive($argv[1], [ 'category' => '1_37'])));
