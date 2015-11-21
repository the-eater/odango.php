<?php

namespace Odango\Nyaa;

use \Odango\Nyaa;
use \Odango\Registry;
use \Odango\NyaaTorrent;
use \Ark\Database\Connection;

class Database extends Nyaa {

    protected $table = 'nyaa';

    protected $database;
    protected $model;

    public function __construct($dbinfo)
    {
        $this->database = new Connection($dbinfo['dsn'], $dbinfo['username'], $dbinfo['password']);
        $this->model    = $this->database->factory("@{$this->table}");
    }

    public function getDatabase()
    {
        return $this->database;
    }

    /**
     * Get torrent feed for given options
     * @param array $options array of options available options consist of: query, category, filter, offset and user
     * @return NyaaTorrent[]
     */
    public function getFeed($options = []) {
        $pool = new \Stash\Pool(new \Stash\Driver\Sqlite());

        $cache = $pool->getItem('nyaa/feed/database/'.hash('sha512', json_encode($options)));

        if ($cache->isMiss()) {
            $cache->lock();

            $results = $this->getResults($options);
            $torrents = [];

            foreach ($results as $result) {
                $torrents[] = NyaaTorrent::fromArray($result);
            }

            $cache->set($torrents, 864000);
        }

        $data = $cache->get();

        return $data;
    }

    private function getResults($options)
    {
        $query = $this->database->builder()
            ->select()
            ->from($this->table);
        $params = [];
        $where = [];

        if (isset($options['category'])) {
            $where[] = 'categoryID = :category';
            $params['category'] = $options['category'];
        }

        if (isset($options['user'])) {
            $where[] = 'submitterID = :user';
            $params['user'] = $options['user'];
        }

        foreach (explode(' ', $options['query']) as $index => $queryBit) {
            $where[] = 'title LIKE :queryBit' . $index;
            $params['queryBit' . $index] = '%'.str_replace('%', "\\%", $queryBit).'%';
        }

        $query->where(implode(' AND ', $where));

        return $query->queryAll($params);
    }

    public function canProvideFullData()
    {
        return true;
    }
}
