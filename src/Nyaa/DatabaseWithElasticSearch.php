<?php

namespace Odango\OdangoPhp\Nyaa;

class DatabaseWithElasticSearch extends Database {
    public function getResults($options)
    {
        $client = Elasticsearch\ClientBuilder::create()->build();

        $query = [
            'match' => [
                "title" => $options['query']
            ]
        ];

        if (isset($options['user']) || isset($options['category'])) {
            $term = [];
            if (isset($options['user'])) {
                $term['user'] = $options['user'];
            }

            if (isset($options['category'])) {
                $term['category'] = $options['category'];
            }

            $query = [
                'filtered' => [
                    'query' => $query,
                    'filter' => [
                        'term' => $term
                    ]
                ]
            ];
        }

        $results = $client->search([
            'query'  => $query,
            'fields' => [],
            'size'   => 1000
        ]);

        $hits = $results['hits']['hits'];

        if (empty($hits)) {
            return [];
        }

        $where = "id IN( ";

        foreach ($hits as $hit) {
            $where .= $hits['_id'] . ', ';
        }

        $where = rtrim($where, ', ');
        $where .= " )";

        return $this->getDatabase()->builder()
            ->select()
            ->from($this->table)
            ->where($where)
            ->queryAll();
    }
}
