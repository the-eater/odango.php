<?php

include '../vendor/autoload.php';

$titles = \Odango\AniDbTitles::construct([ 'dsn' => 'mysql:host=localhost;dbname=odango', 'username'=> 'root', 'password' => null ]);

$titles->createTable(false);
