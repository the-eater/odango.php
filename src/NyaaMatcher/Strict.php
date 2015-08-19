<?php

namespace Odango\NyaaMatcher;

class Strict {

  /**
   * Creates a new instance of NyaaMatcher\Strict
   * @return NyaaMatcher\Strict
   */
  public static function construct()
  {
    return new static();
  }

  public function __invoke($title, $query)
  {
    $titleNormalized = preg_replace('~[^A-z0-9]+~', ' ', strtolower(trim($title)));
    $queryNormalized = preg_replace('~[^A-z0-9]+~', ' ', strtolower(trim($query)));
    
    return $titleNormalized === $queryNormalized;
  }
}
