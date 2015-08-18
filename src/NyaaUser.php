<?php

namespace Odango;

class NyaaUser {

  public $name;
  public $id;
  public $aliases;

  /**
   * Constructs a new NyaaUser
   * @param string $name The name this NyaaUser works under
   * @param int $id The id of the user
   * @param array $aliases Array with aliases this NyaaUser works under
   * @return NyaaUser
   */
  static function construct($name, $id, $aliases = [])
  {
    $user = new NyaaUser();
    $user->name = $name;
    $user->id = $id;
    $user->aliases = $aliases;

    return $user;
  }

  /**
   * Gets a list of known NyaaUsers
   * @return NyaaUser[]
   */
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
