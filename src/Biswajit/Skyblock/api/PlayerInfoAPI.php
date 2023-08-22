<?php

namespace Biswajit\Skyblock\api;

use pocketmine\Server;
use Biswajit\Skyblock\api\ConfigAPI;
use pocketmine\player\Player;

use Biswajit\Skyblock\Skyblock;

class PlayerInfoAPI
{
  
  /** @var string */
  private string $PlayerName;
  
  public function __construct($player)
  {
    if($player instanceof Player)
    {
      $this->PlayerName = $player->getName();
    }else{
      $this->PlayerName = $player;
    }
  }
  
  /**
   * @return String|Null
   */
  public function getIsland(): ?string
  {
    $File = $this->getFile();
    $Island = $File->getIsland();
    return $Island;
  }
  
  /**
   * @return bool
   */
  public function hasSkyblock(): bool
  {
    if(file_exists($this->getSource()->getDataFolder() . "players/" . $this->PlayerName . ".yml"))
    {
      $Bool = true;
    }else{
      $Bool = false;
    }
    
    return $Bool;
  }
  
  /**
   * @return ConfigAPI
   */
  public function getFile(): ConfigAPI
  {
    $Config = new ConfigAPI($this->PlayerName);
    return $Config;
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