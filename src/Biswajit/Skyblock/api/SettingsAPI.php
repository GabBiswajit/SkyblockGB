<?php

namespace Biswajit\Skyblock\api;

use pocketmine\Server;
use pocketmine\player\Player;

use Biswajit\Skyblock\Skyblock;

class SettingsAPI
{
  
  public function __construct()
  {
  }
  
  public function getSource(): Skyblock
  {
    return Skyblock::getInstance();
  }
  
}