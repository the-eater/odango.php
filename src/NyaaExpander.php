<?php

namespace Odango;

class NyaaExpander {
 
  protected $expandables = [
    [
      "S2",
      "Second Season",
      "2nd Season"
    ],
    [
      "S3",
      "Third Season",
      "3rd Season"
    ]
  ];

  public static function construct()
  {
    $matcher = new static();
    return $matcher;
  }

  public function expand($title)
  {
    $titles = [$title];
    foreach ($this->expandables as $exapandable) {
      foreach ($titles as $title) {
        foreach ($exapandable as $id => $repl) {
          if (strpos($title, $repl) !== false) {
            foreach ($exapandable as $nid => $replace) {
              if ($nid !== $id) {
                $titles[] = str_replace($repl, $replace, $title);
              }
            }

            break;
          }
        }
      }
    }

    return $titles;
  }
}
