<?php

namespace Odango\OdangoPhp;

class NyaaTorrent {
  public $title;
  public $torrentUrl;
  public $siteUrl;
  public $category;
  public $publishDate;
  public $userId;
  public $torrentId;
  public $recordUpdated;


  // description data
  public $seeds;
  public $leechers;
  public $downloads;
  public $size;

  public $meta;

  /**
   * Creates a new NyaaTorrent instance from a simple xml element
   *
   * @param \SimpleXmlElement $xml The SimpleXmlElement from the Nyaa feed containing the torrent info
   * @param int $userId The userid of this torrent
   * @return NyaaTorrent
   */
  public static function fromSimpleXml($xml, $userId = null)
  {
    $torrent = new NyaaTorrent();
    $torrent->title = (string)$xml->title;
    $torrent->setInfoFromDescription((string)$xml->description);
    $torrent->meta = NyaaMeta::createFromTitle($torrent->title);

    $torrent->torrentUrl = (string)$xml->link;
    $torrent->siteUrl = (string)$xml->guid;
    $torrent->category = (string)$xml->category;
    $torrent->publishDate = \DateTime::createFromFormat('D, d M Y H:i:s T', $xml->pubDate, new \DateTimeZone('UTC'));
    $torrent->userId = $userId;

    return $torrent;
  }

  public static function fromArray($array)
  {
    $torrent = new NyaaTorrent();
    $torrent->title = $array['title'];
    $torrent->seeds = $array['seeders'];
    $torrent->leechers = $array['leechers'];
    $torrent->downloads = $array['downloads'];
    $torrent->size = $array['filesize'];
    $torrent->category = $array['categoryID'];
    $torrent->torrentId = $array['id'];
    $torrent->userId = $array['submitterID'];
    $torrent->recordUpdated = $array['record_updated'];
    $torrent->publishDate = \DateTime::createFromFormat('U', $array['date']);
    $torrent->meta = NyaaMeta::createFromTitle($torrent->title);

    $torrent->torrentUrl = "http://www.nyaa.se/?page=download&tid=" . $torrent->torrentId;
    $torrent->siteUrl    = "http://www.nyaa.se/?page=view&tid=" . $torrent->torrentId;

    return $torrent;
  }

  /**
   * Sets the torrent info from the description string
   * @param string $description The description to parse info from
   */
  public function setInfoFromDescription($description)
  {
    // 1 seeder(s), 20 leecher(s), 63 download(s) - 793.9 MiB
    if (!preg_match('~([0-9]+) seeder\(s\), ([0-9]+) leecher\(s\), ([0-9]+) download\(s\)(?: - ([0-9]+(\.[0-9]+)))?~i', $description, $match)) {
      return;
    }

    $this->seeds = intval($match[1]);
    $this->leechers = intval($match[2]);
    $this->downloads = intval($match[3]);
    if (isset($match[4])) {
      // MB -> B
      $this->size = floatval($match[4]) * 1000000;
    }
  }

  /**
   * Gets a uniqe hash for this group, title, quality and type (when this is a special it will be appended with a microtime string so it will always be unique)
   * @return string
   */
  public function getSeriesHash()
  {
    $hash = [];

    if ($this->getMeta('title') !== null) {
      $hash[] = $this->getMeta('title');
    }

    if ($this->getMeta('group') !== null) {
      $hash[] = $this->getMeta('group');
    }

    if ($this->getMeta('quality') !== null) {
      $hash[] = $this->getMeta('quality');
    }

    if ($this->getMeta('type')) {
      $hash[] = $this->getMeta('type');

      if ($this->getMeta('special') !== null) {
        $hash[] = $this->getMeta('special') . '.' . md5($this->size . $this->recordUpdated);
      }

      if ($this->getMeta('season') !== null) {
        $hash[] = $this->getMeta('season');
      }
    }

    return implode('-', $hash);
  }

  /**
   * Gets a unique hash for this item in the series, different version have the same item hash
   * @return string
   */
  public function getItemHash()
  {
    return $this->getSeriesHash() . '#' . $this->getSeriesNumber();
  }

  /**
   * Gets the number in the series (always returns 0 for batch, special and season, for a collection it returns the first ep in that collection)
   * @return int
   */
  public function getSeriesNumber()
  {
    $type = $this->getMeta('type');

    switch ($type) {
      case 'batch':
      case 'special':
      case 'season':
        return 0;
      case 'ep':
        return $this->getMeta('ep');
      case 'volume':
        return $this->getMeta('volume');
      case 'collection':
        return $this->getMeta('collection')[0];

    }
  }

  /**
   * Gets the user id for this torrent
   * @return int
   */
  public function getUserId()
  {
    if ($this->userId === null) {
      $this->userId = $this->fetchUserId();
    }

    return $this->userId;
  }

  /**
   * Get the id of this torrent
   * @return int
   */
  public function getTorrentId()
  {
    if ($this->torrentId === null) {
      $this->torrentId = false;

      if (preg_match('~tid=([0-9]+)~', $this->torrentUrl, $match)) {
        $this->torrentId = intval($match[1]);
      }
    }

    return $this->torrentId;
  }

  /**
   * Gets the user id by getting the overview of the torrent page and looking for the user id there
   * @return int
   */
  private function fetchUserId()
  {
    $pool = Registry::getStash();

    $cache = $pool->getItem('nyaa/user/'.$this->getTorrentId());

    if ($cache->isMiss()) {
      // sadly we have to use the site since nothing provides the user id
      $userId = false;
      $html = file_get_contents($this->siteUrl);

      // lets not depend on the actual domain
      if(preg_match('~\/\?user\=([0-9]+)~i', $html, $match)) {
        $userId = intval($match[1]);
      }

      $cache->set($userId);
    }

    return $cache->get();
  }

  /**
   * Gets the NyaaMeta object or a meta value from the meta object if an argument is given
   * @param string $meta Meta name to get
   * @return mixed
   */
  public function getMeta($meta = null)
  {
    if ($meta === null) {
      return $this->meta;
    }

    return $this->meta->get($meta);
  }

  public function toArray()
  {
    return [
      "title" => $this->title,
      "info"  => [
        "seeds"       => $this->seeds,
        "leechers"    => $this->leechers,
        "size"        => $this->size,
        "downloads"   => $this->downloads,
        "torrentId"   => $this->getTorrentId(),
        "userId"      => $this->userId,
        "category"    => $this->category,
        "publishDate" => $this->publishDate
      ],
      "meta"           => $this->getMeta()->toArray(),
      "seriesHash"     => $this->getSeriesHash(),
      "itemHash"       => $this->getItemHash(),
      "seriesNumber"   => $this->getSeriesNumber(),
      "torrentUrl"     => $this->torrentUrl,
      "torrentId"     => $this->torrentId,
      "siteUrl"        => $this->siteUrl
    ];
  }
}
