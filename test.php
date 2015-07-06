<?php
include 'Nyaa.php';
include 'NyaaCollector.php';


var_dump((new NyaaCollector)->collectPerUser('Log Horizon', ['category'=> '1_37']));