<?php

include '../vendor/autoload.php';

Odango\AniDbTitles::construct([ 'dsn' => 'mysql:dbname=odango;host=localhost', 'username' => 'root', 'password' => null ])->syncDatabase();
