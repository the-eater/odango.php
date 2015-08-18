<?php

namespace Odango;

class NyaaMeta {

  /**
   * The found (sub)group
   * @var string
   */
  private $group;

  /**
   * The found qaulity (can be #x#, #p or just #)
   * @var string
   */
  private $quality;

  /**
   * The found audio format
   * @var string
   */
  private $audio;

  /**
   * The found video container (mp4, avi, mkv, etc.)
   * @var string
   */
  private $container;

  /**
   * The found type of the torrent (batch, ep, collection, volume, season and special)
   * @var string
   */
  private $type;

  /**
   * The found episode number
   * @var int
   */
  private $ep;

  /**
   * The found volume number
   * @var int
   */
  private $volume;

  /**
   * The found title
   * @var string
   */
  private $title;

  /**
   * The found video codec
   * @var string
   */
  private $video;

  /**
   * The found crc32
   * @var string
   */
  private $crc32;

  /**
   * The found source of this torrent (BD, DVD)
   * @var string
   */
  private $source;

  /**
   * The found season of this torrent
   * @var int
   */
  private $season;

  /**
   * Mapped but undocumented values
   * @var array
   */
  private $rest = [];

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

  /**
   * Gets the meta value by name, null returned if not found
   * @param string $meta
   * @return mixed
   */
  public function get($meta)
  {
    if (property_exists($this, $meta)) {
      return $this->$meta;
    }

    return isset($this->rest[$meta]) ? $this->rest[$meta] : null;
  }

  /**
   * Sets the meta value by name
   * @param string $meta
   * @param mixed $value
   */
  public function set($meta, $value)
  {
    if (property_exists($this, $meta)) {
      $this->$meta = $value;
    }

    $this->rest[$meta] = $value;
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
}
