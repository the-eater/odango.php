<?php

namespace Odango;

class NyaaSet {
  private $allTorrents = [];
  private $torrents = []; 

  public function add($torrent)
  {
    $this->allTorrents[] = $torrent; 

  }

  public function getTorrents()
  {
    return $this->torrents;
  }
}
