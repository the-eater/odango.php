<?php

namespace Odango\OdangoPhp\NyaaMatcher;

class Fuzzy {

  public $minMatches;
  public $maxDistance;

  /**
   * Creates a new instance of NyaaMatcher\Fuzzy
   * @param int $minMatches The ratio of minimal amount of words that should fall in the max distance to match (0 - 1)
   * @param int $maxDistance The max distance of how different a word may be to match
   * @return NyaaMatcher\Fuzzy
   */
  public static function construct($minMatches = .7, $maxDistance = .2)
  {
    $fuzzy = new static();
    $fuzzy->minMatches = $minMatches;
    $fuzzy->maxDistance = $maxDistance;

    return $fuzzy;
  }

  public function __invoke($title, $query)
  {
    $titleParts = array_filter(preg_split('~[^A-z0-9]+~', strtolower($title)));
    $queryParts = array_filter(preg_split('~[^A-z0-9]+~', strtolower($query)));

    $matches    = 0;
        
    for ($i = 0; $i < count($queryParts); $i++) {
      $queryPart = $queryParts[$i];     

      for ($j = 0; $j < count($titleParts); $j++) {
        $titlePart   = $titleParts[$j];
        $distance    = levenshtein($titlePart, $queryPart);
        $maxDistance = ceil(strlen($queryPart) * $this->maxDistance);

        if ($maxDistance > $distance) {
          $matches++;
          break;
        }
      }
    } 

    $minMatches = ceil($this->minMatches * count($queryPart));
    return $minMatches <= $matches;
  }
}
