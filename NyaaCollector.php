<?php

namespace Odango;

class NyaaCollector {

	protected $nyaa;
	protected $matcher;

	public function setNyaa($nyaa)
	{
		$this->nyaa = $nyaa;
	}

	public function getNyaa()
	{
		if ($this->nyaa === null) {
			$this->setNyaa(new Nyaa());
		}

		return $this->nyaa;
	}

	public function setMatcher($matcher)
	{
		$this->matcher = $matcher;
	}

	public function getMatcher()
	{
		if ($this->matcher === null) {
			$this->setMatcher(new NyaaMatcher_Fuzzy());
		}

		return $this->matcher;
	}

	protected function getFeed($query, $options)
	{
		$options = array_merge(['query' => $query], $options);
		$nyaa = $this->getNyaa();
		return $nyaa->getFeed($options);
	}

	public function filterDuplicates($torrents)
	{
		$arr;

		foreach ($torrents as $torrent) {
			$arr[$torrent->getTorrentId()] = $torrent;
		}

		return array_values($arr);
	}

	public function mapByHash($feed) 
	{
		$torrents = [];

		foreach ($feed as $torrent) {
			$hash = $torrent->getSeriesHash();

			if(!isset($torrents[$hash])) {
				$torrents[$hash] = [];
			}

			$torrents[$hash][] = $torrent;
		}

		return $torrents;
	}

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

	public function collect($query, $options = [])
	{
		$feed = $this->getFeed($query, $options);

		return $this->mapByHash($feed);
	}
}
