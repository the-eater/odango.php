<?php

namespace Odango;

use \Curl\Curl;

class AniDbTitles {
  protected $titleDumpUrl = 'http://anidb.net/api/anime-titles.xml.gz';

  public function fillDatabase()
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

  public function getAlternativeTitles($title)
  {
    
  }
}
