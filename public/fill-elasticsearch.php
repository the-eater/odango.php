<?php

ini_set('memory_limit','512M');

include '../vendor/autoload.php';
$db = new Ark\Database\Connection('mysql:dbname=odango', 'root');

$client = Elasticsearch\ClientBuilder::create()->build();

$torrents = $db->builder()
    ->select('id, categoryID, submitterID, title')
    ->from('nyaa')
    ->queryAll();

$body = [];
$amount = count($torrents);
foreach ($torrents as $i => $torrent) {
    $body[] = [
        'index' =>  [
            '_index' => 'odango',
            '_type'  => 'torrent-title',
            '_id'    => $torrent['id']
        ]
    ];

    $body[] = [
        'title'    => $torrent['title'],
        'category' => $torrent['categoryID'],
        'user'     => $torrent['submitterID']
    ];

    if ($i % 1000 == 999 || ($i+1) === $amount) {
        $k = $i - 1000;
        echo "Inserting [$k->$i/$amount]\n";
        $client->bulk([
            'body' => $body
        ]);

        $body = [];
    }
}
