<?php

/**
 * ███████╗██╗  ██╗██╗   ██╗██╗███████╗██╗      █████╗ ███╗   ██╗██████╗ 
 * ██╔════╝██║ ██╔╝╚██╗ ██╔╝██║██╔════╝██║     ██╔══██╗████╗  ██║██╔══██╗
 * ███████╗█████╔╝  ╚████╔╝ ██║███████╗██║     ███████║██╔██╗ ██║██║  ██║
 * ╚════██║██╔═██╗   ╚██╔╝  ██║╚════██║██║     ██╔══██║██║╚██╗██║██║  ██║
 * ███████║██║  ██╗   ██║   ██║███████║███████╗██║  ██║██║ ╚████║██████╔╝
 * ╚══════╝╚═╝  ╚═╝   ╚═╝   ╚═╝╚══════╝╚══════╝╚═╝  ╚═╝╚═╝  ╚═══╝╚═════╝ 
*/

namespace Biswajit\Skyblock;

use pocketmine\Server;
use pocketmine\player\Player;

use Biswajit\Skyblock\API;
use Biswajit\Skyblock\menu\UI;
use pocketmine\item\VanillaItems;
use Biswajit\Skyblock\menu\GUI;
use pocketmine\world\World;
use Biswajit\Skyblock\EventHandler;
use pocketmine\entity\Skin;
use pocketmine\utils\Color;
use pocketmine\entity\Human;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use pocketmine\entity\Entity;
use pocketmine\world\Position;
use pocketmine\entity\Location;
use Biswajit\Skyblock\api\VariablesAPI;
use pocketmine\plugin\PluginBase;
use muqsit\invmenu\InvMenuHandler;
use pocketmine\item\ItemIdentifier;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\entity\EntityFactory;
use pocketmine\scheduler\ClosureTask;
use pocketmine\block\tile\TileFactory;
use pocketmine\entity\EntityDataHelper;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;

class Skyblock extends PluginBase
{
  
  /** @var Instance */
  private static $instance;
 
  
  public function onEnable(): void 
  {
    self::$instance = $this;
    $this->createplayerFolder();
    $this->createunbreakablesFolder();
    $this->saveResource("world.zip");
    $this->getServer()->getPluginManager()->registerEvents(new EventHandler($this), $this);
    if(!InvMenuHandler::isRegistered())
    {
      InvMenuHandler::register($this);
    }
    $Variables = new VariablesAPI();
    $FormAPI = $this->getServer()->getPluginManager()->getPlugin("FormAPI");
    if($FormAPI === null)
    {
    $this->getServer()->getLogger()->warning("FormAPI not found");
     }      
  }
  
  public static function getInstance(): Skyblock
  {
    return self::$instance;
  }
  
  public function getUI(): UI
  {
    $ui = new UI($this);
    return $ui->getInstance();
  }
  
  public function getGUI(): GUI
  {
    $gui = new GUI($this);
    return $gui->getInstance();
  }
  
  public function getAPI(): API
  {
    $api = new API($this);
    return $api->getInstance();
  }
  
  public function getConfigFile()
  {
    $this->saveResource("config.yml");
    $config = $this->getConfig();
    return $config;
  }
  
  public function getPlayerFile($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $this->saveResource("players/$playerName.yml");
    $playerFile = new Config($this->getDataFolder() . "players/$playerName.yml", Config::YAML, [
      ]);
    return $playerFile;
  }

  private function createplayerFolder() {
        $pluginDataFolder = $this->getDataFolder();
        $playerFolder = $pluginDataFolder . "players/";

        if (!is_dir($playerFolder)) {
            mkdir($playerFolder, 0777, true);
            $this->getLogger()->info("Player folder created successfully!");
       }
  }

  public function onCommand(CommandSender $player, Command $cmd, string $label, array $args): bool 
  {
    switch($cmd->getName())
    {
      case "skyblock":
        if($player instanceof Player)
        {
          $this->getUI()->MainUI($player);
        }
        break;
      case "join":
        if($player instanceof Player)
        {
          $this->getAPI()->teleportToIsland($player, 10);
        }
        break;
     case "coop":
        if($player instanceof Player)
        {
          if(count($args) === 2)
          {
            switch($args[0])
            {
              case "add":
                $victimName = $args[1];
                $playerName = $player->getName();
                $victim = $player->getServer()->getPlayerByPrefix($victimName);
                if($victim instanceof Player)
                {
                  if($this->getAPI()->getCoOpRole($victim) === "Owner" || $this->getAPI()->getCoOpRole($victim) === "Co-Owner")
                  {
                    $members = count($this->api->getMembers($player));
                    $maxMembers = $this->api->getMaxMembers($player);
                    if($members < $maxMembers)
                    {
                      if($this->getAPI()->addCoOpRequest($victim, $playerName))
                      {
                        $player->sendMessage("§ainvite sent to §e".$victim->getName());
                        $player->sendMessage("§athis invite will expire in 1 minute");
                        $victim->sendMessage("§ayou recieved a coop request from §e$playerName");
                        $victim->sendMessage("§atype: §e/coop accept $playerName");
                      }else{
                        $player->sendMessage("§can error occured");
                      }
                    }else{
                      $player->sendMessage("§can error occured");
                    }
                  }else{
                    $player->sendMessage("§can error occured");
                  }
                }else{
                  $player->sendMessage("§can error occurred");
                }
                break;
              case "accept":
                $victimName = $args[1];
                $playerName = $player->getName();
                $victim = Server::getInstance()->getPlayerByPrefix($victimName);
                if($this->getAPI()->addCoOp($player, $victimName))
                {
                  $player->sendMessage("§aaccepted §e$victimName's §aCoOp request");
                  if($victim instanceof Player)
                  {
                    $victim->sendMessage("§e$playerName §aaccepted your CoOp request");
                  }
                }else{
                  $player->sendMessage("§can error occurred");
                }
                break;
              case "deny":
                $victimName = $args[1];
                $playerName = $player->getName();
                $victim = Server::getInstance()->getPlayerByPrefix($victimName);
                  if($this->getAPI()->removeCoOpRequest($playerName, $victimName))
                  {
                    $player->sendMessage("§adenied §e$victimName's CoOp request");
                    if($victim instanceof Player)
                    {
                      $victim->sendMessage("§e$playerName §adenied your CoOp request");
                    }
                  }else{
                    $player->sendMessage("§can error occurred");
                  }
                break;
              default:
                $player->sendMessage("§ausage: §e/coop [add|accept|remove|deny] <player_name>");
                break;
            }
          }
        }
        break;
      return true;
    }
    return false;
  }
}
