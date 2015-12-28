<?php

namespace Odango\OdangoPhp;

class Odango {
  protected $nyaaCollector;
  protected $aniDbTitles;
  protected $nyaaExpander;

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

  protected function getNyaaExpander()
  {
    if ($this->nyaaExpander === null) {
      $this->nyaaExpander = new NyaaExpander();
    }

    return $this->nyaaExpander;
  }

  public function collect($query, $userIds = [], $options = [])
  {
    $nyaaCollector = $this->getNyaaCollector();
    $aniDbTitles = $this->getAniDbTitles();
    $titles = $aniDbTitles->getAlternativeTitles($query);

    if (empty($titles)) {
      $titles = [$query];
    }

    $nyaaExpander = new NyaaExpander();
    $sets = [];

    foreach ($titles as $title) {
      foreach ($nyaaExpander->expand($title) as $expandedTitle) {
        if (empty($userIds)) {
          $sets = array_merge($sets, $nyaaCollector->collectRecursive($expandedTitle, $options));
        } else {
          $sets = array_merge($sets, $nyaaCollector->collectForUser($expandedTitle, $userIds, $options));
        }
      }
    }

    return array_map(function($set){ return $set->toArray(); }, array_values($sets));
  }
}
