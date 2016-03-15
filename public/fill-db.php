<?php

include '../vendor/autoload.php';
Odango\OdangoPhp\Registry::setDatabase(new Ark\Database\Connection('mysql:dbname=odango', 'root'));
Odango\OdangoPhp\AniDbTitles::construct()->syncDatabase();
