<?php

namespace Odango;

use \Curl\Curl;
use \Ark\Database\Connection;

class AniDbTitles {
  protected $dbInfo;
  protected $table;
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
  static function construct($dbInfo, $table)
  {
    $aniDbTitles = new AniDbTitles();
    $aniDbTitles->table = $table;
    $aniDbtitles->dbInfo = $dbInfo;

    $aniDbTitles->db = new Connection($dbInfo['dsn'], $dbInfo['username'], $dbInfo['password']);
    $aniDbtitles->model = $this->db->factory('@' . $table);

    return $aniDbTitles
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

    $xml = simplexml_load_file($tmpname);
    foreach ($xml as $child) {
      var_dump($child);
    }
  }

  /**
   * Gets all alternative titles for the title given
   * @param string $title The title to get alternative titles for
   * @return array Array consisting of alternative titles for given title
   */
  public function getAlternativeTitles($title)
  {

  }
}
