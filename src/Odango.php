<?php

namespace Odango;

class Odango {
  protected $nyaaCollector;
  protected $aniDbTitles;

  protected function getNyaaCollector()
  {
    if ($this->nyaaCollector === null) {
      $this->nyaaCollector = new NyaaCollector();
    }

    return $this->nyaaCollector;
  }

  protected function getAniDbTitles()
  {
    if ($this->aniDbTitles === null) {
      $this->aniDbTitles = AniDbTitles::construct([ 'dsn' => 'mysql:dbname=odango;host=localhost', 'username' => 'root', 'password' => null ]);
    }

    return $this->aniDbTitles;
  }

  public function collect($query, $userIds = [])
  {
    $nyaaCollector = $this->getNyaaCollector();
    $aniDbTitles = $this->getAniDbTitles();
    $titles = $aniDbTitles->getAlternativeTitles($query);

    if (empty($titles)) {
      $titles = [$query];
    }

    $sets = [];

    foreach ($titles as $title) {
      if (empty($userIds)) {
        $sets = array_merge($sets, $nyaaCollector->collectRecursive($title));
      } else {
        $sets = array_merge($sets, $nyaaCollector->collectForUser($title, $userIds));
      }
    }

    return $sets;
  }
}
