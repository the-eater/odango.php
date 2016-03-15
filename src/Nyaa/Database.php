<?php

namespace Odango\OdangoPhp\Nyaa;

use \Odango\OdangoPhp\Nyaa;
use \Odango\OdangoPhp\Registry;
use \Odango\OdangoPhp\NyaaTorrent;
use \Ark\Database\Connection;

class Database extends Nyaa {

    protected $table = 'odango.nyaa';

    public function getDatabase()
    {
        return Registry::getDatabase();
    }

    /**
     * Get torrent feed for given options
     * @param array $options array of options available options consist of: query, category, filter, offset and user
     * @return NyaaTorrent[]
     */
    public function getFeed($options = []) {
        $pool = Registry::getStash();

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
        $query = $this->getDatabase()->builder()
            ->select()
            ->from($this->table);
        $params = [];
        $where = [];

        if (isset($options['category'])) {
            $where[] = 'nyaa."categoryID" = :category';
            $params['category'] = $options['category'];
        }

        if (isset($options['user'])) {
            $where[] = 'nyaa."submitterID" = :user';
            $params['user'] = $options['user'];
        }

        // ([\\s\\]\\)]|^)Log[_ ]Horizon([\\s\\]\\)]|$)
        $queryBits = explode(' ', $options['query']);

        $where[] = 'nyaa.title LIKE :query';
        $params['query'] = '%' . implode('%', array_map(function ($a){ return str_replace('%', '\\%', $a); }, $queryBits)) . '%';

        //$where[] = 'MATCH(title_index) AGAINST ( :regex )';
        //      $params['regex'] = '([\\s\\]\\)_]|^)' . implode('.*', $queryBits) . '([\\s\\]\\)_]|$)';
       //  $params['regex'] = $options['query'];

        $query->where(implode(' AND ', $where));

        return $query->queryAll($params);

    }

    public function canProvideAllData()
    {
        return true;
    }
}
