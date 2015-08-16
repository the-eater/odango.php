<?php

namespace Odango;

class NyaaMeta {
  private $group;
  private $quality;
  private $audio;
  private $container;
  private $type;
  private $ep;
  private $volume;
  private $title;
  private $video;
  private $crc32;
  private $source;
  private $season;
  private $rest = [];
  private $unparsed = [];  

  public static function createFromArray($array, $unparsed = [])
  {
    $nyaaMeta = new NyaaMeta();

    foreach ($array as $key => $value) {
      $nyaaMeta->set($key, $value);
    }

    $nyaaMeta->setUnparsed($unparsed); 
    return $nyaaMeta;
  }

  public function get($meta)
  {
    if (property_exists($this, $meta)) {
      return $this->$meta;
    }

    return $this->rest[$meta];
  }

  public function set($meta, $value)
  {
    if (property_exists($this, $meta)) {
      $this->$meta = $value;
    }

    $this->rest[$meta] = $value;
  }

  public function getUnparsed() {
    return $this->unparsed;
  }

  public function setUnparsed($arr)
  {
    $this->unparsed = $arr;
  }
}
