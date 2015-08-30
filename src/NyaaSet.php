<?php

namespace Odango;

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

  public function getFirstMeta()
  {
    $torrents = array_values($this->getTorrents());
    if (count($torrents) > 0) {
      $torrent  = $torrents[0];
      return $torrent->getMeta()->toArray();
    }

    return null;
  }

  public function toArray()
  {
    $arr = [
       "meta"     => $this->getFirstMeta(),
       "torrents" => array_map(function ($item) {
           return $item->toArray();
       }, $this->getTorrents())
    ];

    return $arr;
  }  
}
