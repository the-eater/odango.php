<?php

namespace Odango\OdangoPhp;

class NyaaMeta {

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
