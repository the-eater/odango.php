<?php

namespace Odango\OdangoPhp;

class NyaaMeta {
  protected static $indicators = array (
    "quality" => ['/^[0-9]+p$/i','/^[0-9]+x[0-9]+$/i', '720','1080','420'],
    "source" => ['/^dvd(-?rip)?$/i', '/^bd(?:-?rip)?$/i', '/^blu-?ray$/i'],
    "audio" => ['/^aac(-ac3)?$/i', 'mp3', '/^flac(-ac3)?$/i'],
    "video" => ['x264', 'x265', 'avc', 'hevc', 'h.264', 'h.265'],
    "video-depth" => ['/^10b(it)?$/i', '/^8b(it)?$/i', 'hi10p'],
    "container" => ['mp4','mkv', 'avi'],
    "crc32" => ['/^[a-f0-9]{8}$/i'],
    "type" => ['batch', 'ova', 'special', 'ona']
  );


  /**
   * values we were able to indentify
   * @var array
   */
  private $parsed = [];

  /**
   * Tags that couldn't be parsed from this torrent
   * @var array
   */
  private $unparsed = [];

  /**
   * Creates a new NyaaMeta instance from array
   *
   * @param array $array array of tags that could be parsed
   * @param array $unparsed The tags that couldn't be parsed
   * @return NyaaMeta
   */
  public static function createFromArray($array, $unparsed = [])
  {
    $nyaaMeta = new NyaaMeta();

    foreach ($array as $key => $value) {
      $nyaaMeta->set($key, $value);
    }

    $nyaaMeta->setUnparsed($unparsed);
    return $nyaaMeta;
  }

  public static function createFromTitle($title)
  {
    $data = $title;
    $meta     = [];
    $unparsed = [];

    // group
    if (preg_match('~^(?:\[([^\]]+)\]|\(([^\)]+)\)|(.+) >> )([_ ])?~', $data, $match)) {
      $meta['group'] = $match[1] ?: ($match[2] ?: $match[3]);
      if (isset($match[4]) && $match[4] == '_') {
        $data = str_replace('_', ' ', $data);
      }
    }

    // tags
    if(0 < preg_match_all('~(?:\[([^\]]+)\]|\(([^\)]+)\))~', $data, $matches)) {
      for($i = 0; $i < count($matches[0]); $i++) {
        $tag = $matches[1][$i]?:$matches[2][$i];

        // if there is a space in the tag lets test if it are multiple tags or just a sentence
        $splitters = [".", " "];
        $foundSplitter = false;
        foreach ($splitters as $splitter) {
          if (strpos($tag, $splitter) !== false) {
            $foundSplitter = true;
            $notag = false;
            $subtags = explode($splitter, $tag);
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
            } else {
              $foundSplitter = false;
            }
          }
        }

        if (!$foundSplitter) {
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
    if (preg_match('~ (?:(Vol\.? ?([0-9]+) (?:End)?)|([0-9]+(?:\.[0-9]+)?)|(batch(?: (\d+)-(\d+))?|o[vn]a|special)|(([0-9]+)-([0-9]+))(?: complete)?|((s|season )([0-9]+)))( ?v([0-9]+))? ?(\[|\()~i', $data, $match)) {
      if (!empty($match[1])) {
        $meta['type'] = 'volume';
        $meta['volume'] = intval($match[2]);

      } else if (!empty($match[3]) && /* in case a series ends with a number and has BATCH in the tags */ !isset($meta['type'])) {
        $meta['type'] = 'ep';
        $meta['ep'] = floatval($match[3]);
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

    /**
     * If it doesn't have a type, but it has a source (BD / dvd) it's most likely a batch
     */
    if (!isset($meta['type']) && isset($meta['source'])) {
        $meta['type'] = 'batch';
    }

    // title
    if (preg_match('~(?:\[([^\]]+)\]|\(([^\)]+)\)|(.+) >>)?((?:(?!\[[^\]+]\]| [-\~] (?:[0-9]|vol|batch|special|o[nv]a)|( (Vol\. ?)?[0-9]+(-[0-9]+)?(v[0-9]+)? ?)?(\(|\[|\.[a-z0-9]+$)).)+)~i', $data, $match)) {
      if ($match[4]) {
        $meta['title'] = trim($match[4]);
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

    return NyaaMeta::createFromArray($meta, $unparsed);
  }

  /**
   * Solves what type of data an indicator is
   * @param string $data The indicator
   * @return array An array with the first item containing what it represents, and second item containing the normalized version of the data, false is returned when failing to find what is indicated
   */
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


  /**
   * Gets the meta value by name, null returned if not found
   * @param string $meta
   * @return mixed
   */
  public function get($meta)
  {
    return isset($this->parsed[$meta]) ? $this->parsed[$meta] : null;
  }

  /**
   * Sets the meta value by name
   * @param string $meta
   * @param mixed $value
   */
  public function set($meta, $value)
  {
    $this->parsed[$meta] = $value;
  }

  /**
   * Gets all unparsed meta tags
   * @return array
   */
  public function getUnparsed() {
    return $this->unparsed;
  }

  /**
   * Sets all unparsed meta tags
   * @param array $arr
   */
  public function setUnparsed($arr)
  {
    $this->unparsed = $arr;
  }

  public function toArray()
  {
      return array_merge($this->parsed, [
        "unparsed" => $this->unparsed
      ]);
  }
}
