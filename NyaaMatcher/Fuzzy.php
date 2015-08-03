<?php

namespace Odango\NyaaMatcher;

class Fuzzy {

    public $minMatches;
    public $maxDistance;

    function __construct($minMatches = .7, $maxDistance = .2)
    {
        $this->minMatches = $minMatches;
        $this->maxDistance = $maxDistance;
    }

    function __invoke($title, $query)
    {
        $titleParts = array_filter(preg_split('~[^A-z0-9]+~', $title));
        $queryParts = array_filter(preg_split('~[^A-z0-9]+~', $query));
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
        return $minMatches < $matches;
    }

}
