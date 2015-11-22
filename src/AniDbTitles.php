<?php

namespace Odango;

use \Curl\Curl;
use \Ark\Database\Connection;

class AniDbTitles {
  protected $dbInfo = null;
  protected $table = 'AniDbTitle';
  protected $titleDumpUrl = 'http://anidb.net/api/anime-titles.xml.gz';

  protected $db;
  protected $model;

  /**
   * Creates a new AniDbTitles instance
   *
   * @param array $dbInfo Array consisting of dsn, username and password, used to connect to the database where the anidbtitles are stored
   * @param string $table The table to store and access the cached anidb titles
   * @return AniDbTitles The created AniDbTitles instance
   */
  static function construct()
  {
    $aniDbTitles = new AniDbTitles();

    $aniDbTitles->db = Registry::getDatabase();
    $aniDbTitles->model = $aniDbTitles->db->factory('@' . $aniDbTitles->table);

    return $aniDbTitles;
  }

  /**
   * Syncs the database with the up-to-date anidb title dump
   */
  public function syncDatabase()
  {
    $tmpname = '/tmp/odango.php.cache';

    if (!file_exists($tmpname)) {
      $curl = new Curl();
      $curl->setOpt(CURLOPT_ENCODING, "gzip");
      $curl->download($this->titleDumpUrl, $tmpname);
      $curl->close();
    }

    $this->db->exec('START TRANSACTION');
    $this->db->exec('DELETE FROM ' . $this->table);

    $xml = simplexml_load_file($tmpname);
    foreach ($xml as $child) {
      $aid = (int)$child['aid'];
      $added = [];
      foreach($child->title as $title) {
        $type = (string)$title['type'];
        $lang = (string)$title->attributes('xml', true)['lang'];

        if ($lang == 'x-jat' || $lang == 'en') {

          if (!in_array((string)$title, $added)) {
            $added[] = (string)$title;

            $this->model->insert([
              'aniDbId'   => $aid,
              'title'     => (string)$title,
              'isDefault' => $type == 'main' ? 1 :0
            ]);
          } else if ( $type == 'main' ) {
            $this->model->update(['isDefault' => 1], 'AniDbId = '.$aid.' AND title = '.$this->db->quote((string)$title));
          }
        }
      }
    }

    $this->db->exec('COMMIT');
  }

  /**
   * Gets all alternative titles for the title given
   * @param string $title The title to get alternative titles for
   * @return array Array consisting of alternative titles for given title
   */
  public function getAlternativeTitles($title)
  {
    $builder = $this->db->builder();

    return $builder
      ->select('search.title')
      ->from([$this->table . ' as main'])
      ->join($this->table . ' as search', 'main.aniDbId = search.aniDbId')
      ->where('main.title = :title')
      ->queryColumn([ 'title' => $title ]);

  }

  public function getAlternativeTitlesByAnime($title)
  {
    $builder = $this->db->builder();

    $titles = $builder
      ->select('search.*')
      ->from([$this->table . ' as main'])
      ->join($this->table . ' as search', 'main.aniDbId = search.aniDbId')
      ->where('main.title = :title')
      ->groupBy('search.id')
      ->queryAll([ 'title' => $title ]);

    return array_values(array_reduce($titles, function($carry, $item) {
      if ( ! isset($carry[$item['aniDbId']]) ) {
        $carry[$item['aniDbId']] = [
          "aniDbId" => $item['aniDbId'],
          "main"    => "",
          "titles"  => []
        ];
      }

      $carry[$item['aniDbId']]['titles'][] = $item['title'];

      if ($item['isDefault'] == 1) {
        $carry[$item['aniDbId']]['main'] = $item['title'];
      }

      return $carry;
    }, []));
  }

  public function autocomplete($title)
  {
    $builder = $this->db->builder();

    return $builder
      ->distinct()
      ->select('title')
      ->from($this->table)
      ->where('title LIKE :title')
      ->limit(25)
      ->queryColumn([ 'title' => str_replace('%', '\\%', $title).'%' ]);
  }

  public function createTable()
  {
    $createQuery = "CREATE TABLE {$this->table} (
      id INTEGER NOT NULL AUTO_INCREMENT,
      aniDbId INTEGER NOT NULL,
      title VARCHAR(255) NOT NULL,
      isDefault TINYINT NOT NULL,
      PRIMARY KEY (`id`),
      INDEX index_aniDbId (aniDbId),
      INDEX index_aniDbId_IsDefault (aniDbId, isDefault),
      INDEX index_title (title)
    );";

    $this->db->exec($createQuery);
  }
}
