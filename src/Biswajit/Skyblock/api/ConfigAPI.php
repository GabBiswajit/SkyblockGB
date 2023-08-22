<?php

namespace Biswajit\Skyblock\api;

use pocketmine\Server;
use pocketmine\utils\Config;
use pocketmine\player\Player;

use Biswajit\Skyblock\Skyblock;
use Biswajit\Skyblock\API;

class ConfigAPI
{
  
  /** @var Config */
  private Config $Config;
  
  public function __construct($player, bool $NewFile = false)
  {
    if($player instanceof Player)
    {
      $PlayerName = $player->getName();
    }else{
      $PlayerName = $player;
    }
    if($NewFile)
    {
      $this->saveResource("players/" . $PlayerName . ".yml");
    }
    $this->Config = new Config(API::getInstance()->getSource()->getDataFolder() . "players/" . $PlayerName . ".yml", Config::YAML, []);
  }
  
  /**
   * @return String|Null
   */
  public function getIsland(): ?string
  {
    $Island = $this->Config->get("Island");
    return $Island;
  }
  
  /**
   * @return Skyblock
   */
  public function getSource(): Skyblock
  {
    $Skyblock = Skyblock::getInstance();
    return $Skyblock;
  }
  
}