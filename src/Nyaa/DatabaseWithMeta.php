<?php

namespace Odango\OdangoPhp\Nyaa;

class DatabaseWithMeta extends Database {
    public function getResults($options)
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

        $where[] = 'nyaa.meta->>\'title\' = :query';

        $query->where(implode(' AND ', $where));

        return $query->queryAll($params);
    }
}
