<?php

class NyaaUser {

  public $name;
  public $id;
  public $aliases;

  static function construct($name, $id, $aliases = [])
  {
    $user = new NyaaUser();
    $user->name = $name;
    $user->id = $id;

  }

  static function getKnown()
  {
    return [
      NyaaUser::construct('HorribleSubs', 64513),
      NyaaUser::construct('Commie', 76430),
      NyaaUser::construct('Cthuko', 227226, ['Cthune']),
      NyaaUser::construct('DeadFish', 169660),
      NyaaUser::construct('Coalgirls', 62260)
    ];
  }
}
