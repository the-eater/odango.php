<?php

include '../vendor/autoload.php';

$aniDbTitles = \Odango\AniDbTitles::construct([ 'dsn' => 'mysql:dbname=odango;host=localhost', 'username' => 'root', 'password' => null ]);

var_dump($aniDbTitles->autocomplete($argv[1]));
