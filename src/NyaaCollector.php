<?php

namespace Odango\OdangoPhp;

class NyaaCollector {

  /**
   * @var Nyaa
   */
  protected $nyaa;
  /**
   * The matcher used to check if the title actually corresponds with the given title
   * @var \Callable
   */
  protected $matcher;

  /**
   * @param Nyaa $nyaa
   */
  public function setNyaa($nyaa)
  {
    $this->nyaa = $nyaa;
  }

  /**
   * @return Nyaa
   */
  public function getNyaa()
  {
    if ($this->nyaa === null) {
      $this->setNyaa(Registry::getNyaa());
    }

    return $this->nyaa;
  }

  /**
   * @param \Callable $matcher
   */
  public function setMatcher($matcher)
  {
    $this->matcher = $matcher;
  }

  /**
   * @return \Callable
   */
  public function getMatcher()
  {
    if ($this->matcher === null) {
      $this->setMatcher(NyaaMatcher\Strict::construct());
    }

    return $this->matcher;
  }

  /**
   * Gets the torrent feed for given query and options
   * @param string $query Query to search for in the nyaa feed
   * @param array $options Options for the nyaa feed
   * @return NyaaTorrent[]
   */
  protected function getFeed($query, $options)
  {
    $options = array_merge(['query' => $query], $options);
    $nyaa    = $this->getNyaa();
    $feed    = $nyaa->getFeed($options);

    $matcher = $this->getMatcher();

    return array_filter($feed, function ($item) use ($matcher, $query) {
      $title = $item->getMeta('title');
      return $title !== null && $matcher($title, $query);
    });
  }

  /**
   * Return list with unique torrents from given array
   *
   * @param NyaaTorrent[] The list to filter duplicates out
   * @param NyaaTorrent[]
   */
  public function filterDuplicates($torrents)
  {
    $arr = [];

    foreach ($torrents as $torrent) {
      $arr[$torrent->getTorrentId()] = $torrent;
    }

    return array_values($arr);
  }

  /**
   * Maps the torrents by series hash and collects them in a NyaaSet
   *
   * @param NyaaTorrent[] list of torrents to be mapped
   * @param NyaaSet[]
   */
  public function mapByHash($feed)
  {
    $torrents = [];

    foreach ($feed as $torrent) {
      $hash = $torrent->getSeriesHash();

      if(!isset($torrents[$hash])) {
        $torrents[$hash] = new NyaaSet($hash);
      }

      $torrents[$hash]->add($torrent);
    }

    return $torrents;
  }

  /**
   * Searches for torrents per user
   *
   * @param string $query The query to search for
   * @param int[] $users The users to search in
   * @param array $options extra options for the nyaa feed
   * @return NyaaSet[]
   */
  public function collectForUser($query, $users, $options = [])
  {
    $bigFeed = [];

    foreach ($users as $userId) {
      $userOptions = array_merge($options, ['user' => $userId]);
      $userFeed = $this->getFeed($query, $userOptions);
      $bigFeed = array_merge($bigFeed, $userFeed);
    }

    $bigFeed = $this->filterDuplicates($bigFeed);

    return $this->mapByHash($bigFeed);
  }

  /**
   * Searches for torrent and searches for each found torrent in their user for more torrents, to create complete sets
   *
   * @param string $query The query to search for
   * @param array $options Extra options for the nyaa feed
   * @return NyaaSet[]
   */
  public function collectRecursive($query, $options = [])
  {
    $feed = $this->getFeed($query, $options);

    $userIds = [];

    foreach ($feed as $torrent) {
      if (!isset($userIds[$torrent->getSeriesHash()])) {
        $userIds[$torrent->getSeriesHash()] = $torrent->getUserId();
      }
    }

    $userIds = array_unique(array_values($userIds));

    return $this->collectForUser($query, $userIds, $options);
  }

  /**
   * Searches for torrents by query and returns sets created from it
   * Collects recursively if backend doesn't provide all data
   *
   * @param string $query The query to search for
   * @param array $options Extra options for the nyaa feed
   */
  public function collect($query, $options = [])
  {
    if (!$this->getNyaa()->canProvideAllData()) {
        return $this->collectRecursive($query, $options);
    }


    return $this->collectSingleFeed($query, $options);
  }

  /**
   * Searches for torrents by query and returns sets created from it, only fetches one feed.
   *
   * @param string $query
   * @param array $options
   */
  public function collectSingleFeed($query, $options = [])
  {
    $feed = $this->getFeed($query, $options);

    return $this->mapByHash($feed);
  }
}
