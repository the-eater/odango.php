<?php

ini_set('memory_limit','512M');

include '../vendor/autoload.php';
$db = new Ark\Database\Connection('pgsql:dbname=odango', 'eater', 'test');

$client = Elasticsearch\ClientBuilder::create()->build();

$torrents = $db->builder()
    ->select('id, title')
    ->from('odango.nyaa')
    ->where('"categoryID" = :category', [
        'category' => '1_37'
    ])
    ->queryAll();

$amount = count($torrents);
foreach ($torrents as $i => $torrent) {
    $meta = Odango\OdangoPhp\NyaaMeta::createFromTitle($torrent['title'])->toArray();

    $db->builder()
        ->update('odango.nyaa', ["meta" => json_encode($meta)], "nyaa.id = :id", ["id" => $torrent["id"] ]);

    echo "[$i/$amount] Updating... \r";
}

echo "\n";
