<?php

namespace Odango;

class NyaaTorrent {
	protected static $indicators = array (
		"quality" => ['/^[0-9]+p$/i','/^[0-9]+x[0-9]+$/i', '720','1080','420'],
		"source" => ['/^dvd(-?rip)?$/i', '/^bd(?:-?rip)?$/i'],
		"audio" => ['aac', 'mp3', 'flac'],
		"video" => ['x264', 'x265', 'avc', 'hevc', 'h.264', 'h.265'],
		"container" => ['mp4','mkv', 'avi'],
		"crc32" => ['/^[a-f0-9]{8}$/i'],
		"type" => ['batch', 'ova', 'special', 'ona']
	);

	public $title;
	public $torrentUrl;
	public $siteUrl;
	public $category;
	public $publishDate;
	public $userId;
	public $torrentId;


	// description data
	public $seeds;
	public $leechers;
	public $downloads;
	public $size;

	public $meta;

	public static function fromSimpleXml($xml, $userId)
	{
		$torrent = new NyaaTorrent();
		$torrent->title = (string)$xml->title;
		$torrent->setInfoFromDescription((string)$xml->description);
		$torrent->parseMetaInfo();

		$torrent->torrentUrl = (string)$xml->link;
		$torrent->siteUrl = (string)$xml->guid;
		$torrent->category = (string)$xml->category;
		$torrent->publishDate = \DateTime::createFromFormat('D, d M Y H:i:s T', $xml->pubDate, new \DateTimeZone('UTC'));
    $torrent->userId = $userId;

		return $torrent;
	}

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

	public function parseMetaInfo()
	{
		$data     = $this->title;
		$meta     = [];
    $unparsed = [];

		// group
		if (preg_match('~^\[([^\]]+)\]([_ ])?~', $data, $match)) {
			$meta['group'] = $match[1];
			if (isset($match[2]) && $match[2] == '_') {
				$data = str_replace('_', ' ', $data);
			}
		}

		// tags
		if(0 < preg_match_all('~(?:\[([^\]]+)\]|\(([^\)]+)\))~', $data, $matches)) {
			for($i = 0; $i < count($matches[0]); $i++) {
				$tag = $matches[1][$i]?:$matches[2][$i];

				// if there is a space in the tag lets test if it are multiple tags or just a sentence
				if (strpos($tag, ' ') !== false) {
					$notag = false;
					$subtags = explode(' ', $tag);
					$missedTags = 0;
					$subMeta = [];
          $subUnparsed = [];

					foreach ($subtags as $subtag) {
						$solved = self::solveIndicator($subtag);
						if ($solved == false) {
							$missedTags++;
							if ($missedTags > 2) {
								$notag = true;
								break;
							}

              $subUnparsed[] = $subtag;
						} else {
							list($key, $value) = $solved;
							$subMeta[$key] = $value;
						}
					}

					if (!$notag) {
						$meta = array_merge($meta, $subMeta);
            $unparsed = array_merge($unparsed, $subUnparsed);
					}

				} else {
					$solved = self::solveIndicator($tag);
					if ($solved !== false) {
						$meta[$solved[0]] = $solved[1];
					} else {
            $unparsed[] = $tag;
          }
				}
			}
		}

		// extension
		if(preg_match('~\.([a-z0-9]+)$~i', $data, $match)) {
			$meta['container'] = $match[1];
		}

		// vol / type / ep nr.
		if (preg_match('~ (?:(Vol\.? ?([0-9]+))|([0-9]+)|(batch(?: (\d+)-(\d+))?|o[vn]a|special)|(([0-9]+)-([0-9]+))|((s|season )([0-9]+)))( ?v([0-9]+))? ?(\[|\()~i', $data, $match)) {
			if (!empty($match[1])) {
				$meta['type'] = 'volume';
				$meta['volume'] = intval($match[2]);

			} else if (!empty($match[3]) && /* in case a series ends with a number and has BATCH in the tags */ !isset($meta['type'])) {
				$meta['type'] = 'ep';
				$meta['ep'] = intval($match[3]);
			} else if (!empty($match[4])) {
				if (substr(strtolower($match[4]),0, 5) == 'batch') {
					$meta['type'] = 'batch';
          if (isset($match[5])) {
            $meta['collection'] = [intval($match[5]), intval($match[6])];
          }
				} else {
					$meta['type'] = 'special';
					$meta['special'] = strtolower($match[4]);
				}
			} else if (!empty($match[7])) {
				$meta['type'] = 'collection';
				$meta['collection'] = [intval($match[8]), intval($match[9])];
			} else if (!empty($match[9])) {
				$meta['type'] = 'season';
				$meta['season'] = intval($match[11]);
			}

			if (!empty($match[13])) {
				$meta['version'] = intval($match[13]);
			}
		}

		// title
		if (preg_match('~(?:^|\)|\])((?:(?!\[[^\]+]\]| [-\\~] |( (Vol\. ?)?[0-9]+(v[0-9]+)? ?)?(\(|\[|\.[a-z0-9]+$)).)+)~', $data, $match)) {
			if ($match[1]) {
				$meta['title'] = trim($match[1]);
			}
		}
    if (isset($meta['group'])) {
      // since the group has no meaning to the auto tag solver 
      // we got to remove it from the unparsed list
      $groupKey = array_search($meta['group'], $unparsed);
      if ($groupKey !== false) {
        array_splice($unparsed, $groupKey, 1, []);
      }
    }

		$this->meta = NyaaMeta::createFromArray($meta, $unparsed);
	}

	public function getSeriesHash()
	{
		$hash = [];

		if ($this->getMeta('group') !== null) {
			$hash[] = $this->getMeta('group');
		}

		if ($this->getMeta('title') !== null) {
			$hash[] = $this->getMeta('title');
		}

		if ($this->getMeta('quality') !== null) {
			$hash[] = $this->getMeta('quality');
		}

		if ($this->getMeta('type')) {
			$hash[] = $this->getMeta('type');

      if ($this->getMeta('special') !== null) {
        $hash[] = $this->getMeta('special') . '.' . microtime(true);
      }

      if ($this->getMeta('season') !== null) {
        $hash[] = $this->getMeta('season');
      }
    }

		return implode('/', $hash);
	}
  
  public function getItemHash()
  {
    return $this->getSeriesHash() . '#' . $this->getSeriesNumber();
  }

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

	public static function solveIndicator($data) {
		$normData = strtolower(trim($data));
		foreach (self::$indicators as $key => $tests) {
			foreach ($tests as $test) {
				if ($test[0] == '/' && preg_match($test, $normData)) {
					return [$key, $normData];
				} elseif ($normData === $test) {
					return [$key, $normData];
				}
			}
		}

		return false;
	}

	public function getUserId()
	{
		if ($this->userId === null) {
			$this->userId = $this->fetchUserId();
		}

		return $this->userId;
	}

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

	private function fetchUserId()
	{
		// sadly we have to use the site since nothing provides the user id
		$userId = false;
		$html = file_get_contents($this->siteUrl);

		// lets not depend on the actual domain
		if(preg_match('~\/\?user\=([0-9]+)~i', $html, $match)) {
			$userId = intval($match[1]);
		}

		return $userId;
	}

  public function getMeta($meta = null)
  {
    if ($meta === null) {
      return $this->meta;
    }

    return $this->meta->get($meta);
  }
}
