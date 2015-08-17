<?php

namespace Odango;

class NyaaSet {
  private $allTorrents = [];
  private $torrentsByItemHash = []; 

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

  public function getTorrents()
  {
    return array_values($this->torrentsByItemHash());
  }
}
