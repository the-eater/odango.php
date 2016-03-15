<?php

namespace Odango\OdangoPhp;

class NyaaSet {
  private $allTorrents = [];
  private $torrentsByItemHash = [];

  /**
   * Adds a torrent to this torrent set, and overwrites older versions (if not newer preservers older version) of torrents already in this set
   *
   * @param NyaaTorrent $torrent The torrent to add
   */
  public function add($torrent)
  {
    $this->allTorrents[] = $torrent;

    $hash = $torrent->getItemHash();
    if (isset($this->torrentsByItemHash[$torrent->getItemHash()])) {
      $otherTorrent = $this->torrentsByItemHash[$hash];

      if ($otherTorrent->getMeta('version') < $torrent->getMeta('version') ) {
        $torrentsByItemHash[$hash] = $torrent;
      }
    } else {
      $this->torrentsByItemHash[$hash] = $torrent;
    }
  }

  /**
   * Gets all torrents in this set
   * @return NyaaTorrent[]
   */
  public function getTorrents()
  {
    return array_values($this->torrentsByItemHash);
  }

  public function getSortedTorrents()
  {
    $torrents = $this->getTorrents();
    usort($torrents, function ($a, $b) {
        return $a->getSeriesNumber() - $b->getSeriesNumber();
    });
    return $torrents;
  }

  public function getFirstMeta()
  {
    $torrents = array_values($this->getTorrents());
    if (count($torrents) > 0) {
      $torrent  = $torrents[0];
      return $torrent->getMeta()->toArray();
    }

    return null;
  }

  public function getInfo()
  {
    $seeds    = [];
    $leechers = [];
    $size     = [];
    $amount   = 0;

    foreach ($this->getTorrents() as $torrent) {
        $seeds[] = intval($torrent->seeds);
        $leechers[] = intval($torrent->leechers);
        $size[] = intval($torrent->size);
        $amount++;
    }

    return [
        "amount" => $amount,
        "seeds" => [
            "min" => min($seeds),
            "max" => max($seeds),
            "avg" => array_sum($seeds) / count($seeds)
        ],
        "leechers" => [
            "min" => min($leechers),
            "max" => max($leechers),
            "avg" => array_sum($leechers) / count($leechers)
        ],
        "size" => [
            "min" => min($size),
            "max" => max($size),
            "avg" => array_sum($size) / count($size),
            "total" => array_sum($size)
        ]
    ];
  }

  public function toArray()
  {
    $arr = [
       "meta"     => $this->getFirstMeta(),
       "info"     => $this->getInfo(),
       "torrents" => array_map(function ($item) {
           return $item->toArray();
       }, $this->getSortedTorrents())
    ];

    return $arr;
  }
}
