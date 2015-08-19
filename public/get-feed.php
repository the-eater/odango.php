<?php

include '../vendor/autoload.php';

$collector = new \Odango\NyaaCollector();
$collector->setMatcher(\Odango\NyaaMatcher\Strict::construct());
var_dump(array_keys($collector->collectRecursive($argv[1], [ 'category' => '1_37'])));
