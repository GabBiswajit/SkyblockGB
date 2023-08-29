<?php

namespace Biswajit\Skyblock\menu;

use pocketmine\Server;
use pocketmine\player\Player;

use jojoe77777\FormAPI\SimpleForm;
use jojoe77777\FormAPI\CustomForm;

use Biswajit\Skyblock\API;
use Biswajit\Skyblock\Skyblock;
use pocketmine\item\Item;
use pocketmine\utils\Config;
use pocketmine\scheduler\ClosureTask;

class UI
{
  
  /** @var Instance */
  private static $instance;
  
  /** @var Skyblock */
  private $source;
  
  /** @var API */
  public $api;
  
  /** @var Config */
  public $players;
  
  /** @var Config */
  public $config;
  
  public function __construct(Skyblock $source)
  {
    self::$instance = $this;
    $this->source = $source;
    $this->api = $source->getInstance()->getAPI();
    $this->config = $source->getInstance()->getConfigFile();
  }
  
  public static function getInstance(): UI
  {
    return self::$instance;
  }
  
  public function addMemberMenu(Player $player)
  {
    $form = new CustomForm(
      function(Player $player, $data)
      {
        if($data === null)
        {
          $player->sendMessage("§cPlease enter a name tag");
          return true;
        }
        $result = (string) $data[1];
        $victim = Server::getInstance()->getPlayerByPrefix($result);
        if($victim instanceof Player)
        {
          $members = count($this->api->getMembers($player));
          $maxMembers = $this->api->getMaxMembers($player);
          if($members < $maxMembers)
          {
            $playerName = $player->getName();
            if($this->api->addCoOpRequest($victim, $playerName))
            {
              $player->sendMessage("§ainvite sent to §e".$victim->getName());
              $player->sendMessage("§athis invite will expire in 1 minute");
              $victim->sendMessage("§ayou recieved a coop request from §e$playerName");
              $victim->sendMessage("§atype: §e/coop accept $playerName");
              return true;
            }else{
              $player->sendMessage("§can error occured");
              return false;
            }
          }else{
            $player->sendMessage("§can error occured");
            return false;
          }
        }else{
          $player->sendMessage("§cThat Player Is Not Online");
          return false;
        }
      }
    );
    $form->setTitle("§bAdd §3Member");
    $form->addInput("Please Enter The NameTag");
    $form->sendToPlayer($player);
  }
  public static function getSource(): Skyblock
  {
    $Skyblock = Skyblock::getInstance();
    return $Skyblock;
  }
    public function MainUI(Player $player)
  {
    $Form = new SimpleForm(
      function(Player $player, $Data): bool
      {
        if(is_null($Data))
        {
          return true;
        }
        
        switch($Data)
        {
          case 0:
            $this->Issettings($player);
            break;
          case 1:
            $GUI = $this->api->getSource()->getGUI();
            $GUI->VisitMenu($player);
            break;
          case 2:
            $this->api->teleportToIsland($player, 11);
            break;
          case 3:
            if($this->api->getCoOpRole($player) === "Owner" || $this->api->getCoOpRole($player) === "Co-Owner")
           {
                $this->addMemberMenu($player);
                  }
            break;
          case 4:
               $this->delis($player);
            break;
        }
        return true;
      }
    );
    $Form->setTitle("§l§cSKYBLOCK SETTINGS");
    $Form->addButton("§eIsland Settings", 1, "https://icons.iconarchive.com/icons/dtafalonso/android-lollipop/256/Settings-icon.png");
    $Form->addButton("§eVisit Island", 1, "https://cdn-icons-png.flaticon.com/128/1541/1541400.png");
    $Form->addButton("§eTeleport To Island", 1, "https://cdn-icons-png.flaticon.com/128/619/619005.png");
    $Form->addButton("§eAdd Members", 1, "https://cdn-icons-png.flaticon.com/128/3315/3315183.png");
    $Form->addButton("§eReset Island", 1, "https://cdn-icons-png.flaticon.com/128/3496/3496416.png");
    $Form->sendToPlayer($player);
  }
  public function Lock(Player $player)
  {
    $Form = new SimpleForm(
      function(Player $player, $Data): bool
      {
        if(is_null($Data))
        {
          return true;
        }
        
        switch($Data)
        {
          case 0:
            if($this->api->lockIsland($player))
          {
            $player->sendMessage("§aSuccessfully locked your island");
          }else{
            $player->sendMessage("§cError can't lock your island");
          }
            break;
          case 1:
            if($this->api->unlockIsland($player))
          {
            $player->sendMessage("§aSuccessfully unlocked your island");
          }else{
            $player->sendMessage("§cError can't unlock your island");
          }
            break;
        }
        return true;
      }
    );
    $Form->setTitle("§bVisit Settings");
    $Form->addButton("§eLock Visit", 1, "https://pngimg.com/uploads/minecraft/minecraft_PNG63.png");
    $Form->addButton("§eUnlock Visit", 1, "https://pngimg.com/uploads/minecraft/minecraft_PNG63.png");
    $Form->sendToPlayer($player);
  }
  public function delis(Player $player)
  {
    $name = $player->getName();
    $Form = new SimpleForm(
      function(Player $player, $Data): bool
      {
        if(is_null($Data))
        {
          return true;
        }
        
        switch($Data)
        {
          case 0:
               $playerName = $player->getName();
               $playerFile = $this->api->getSource()->getPlayerFile($playerName);
               $this->api->getSource()->getServer()->getWorldManager()->loadWorld($playerFile->get("Island"));
               $world = $this->api->getSource()->getServer()->getWorldManager()->getWorldByName($playerFile->get("Island"));
               $this->api->getSource()->getServer()->getWorldManager()->unloadWorld($world);
               $this->api->deleteDirectory(Server::getInstance()->getDataPath() . "worlds/" . $playerFile->get("Island"));
               $this->api->createIsland($playerName);
               $this->api->teleportToIsland($player, 11);
            break;
          case 1:
            $this->MainUI($player);
            break;
        }
        return true;
      }
    );
    $Form->setTitle("§bReset Island");
    $Form->setContent("§6Hello, §e$name\n\n§aAre Sure To Reset Your Island ?" );
    $Form->addButton("§eYes");
    $Form->addButton("§eNo");
    $Form->sendToPlayer($player);
  }
  public function Issettings(Player $player)
  {
  	if($this->api->getCanDropItems($player))
    {
      $canDrop = "§aEnabled";
    }else{
      $canDrop = "§cDisabled";
    }
    $Form = new SimpleForm(
      function(Player $player, $Data): bool
      {
        if(is_null($Data))
        {
          return true;
        }
        
        switch($Data)
        {
          case 0:
            $this->Limit($player); 
            break;
          case 1:
            if($this->api->setIslandSpawn($player))
          {
            $player->sendMessage("§aSuccessfully changed spawn of the island");
          }else{
            $player->sendMessage("§cError can't change spawn of the island");
          }
            break;
          case 2:
          $canDrop = $this->api->getCanDropItems($player);
          if($canDrop)
          {
            $updateCanDrop = false;
          }else{
            $updateCanDrop = true;
          }
          if($this->api->setCanDropItems($player, $updateCanDrop))
          {
            if($updateCanDrop)
            {
              $player->sendMessage("§aVisiotrs dropping/picking items enabled");
            }else{
              $player->sendMessage("§aVisiotrs dropping/picking items disabled");
            }
          }else{
            $player->sendMessage("§cError can't Update Droping Items");
          }
            break;
          case 3:
          if($this->api->getCoOpRole($player) === "Owner" || $this->api->getCoOpRole($player) === "Co-Owner")
          {
        	$GUI = $this->api->getSource()->getGUI();
            $GUI->ManageMembersMenu($player);
          }
            break;
          case 4:
           $this->Lock($player);
            break;
        }
        return true;
      }
    );
    $Form->setTitle("§bSkyblock Settings");
    $Form->addButton("§eVisitors Limit", 1, "https://cdn-icons-png.flaticon.com/128/854/854878.png");
    $Form->addButton("§eSet Island Spawn", 1, "https://cdn-icons-png.flaticon.com/128/166/166344.png");
    $Form->addButton("§eVisitors Drop\n§a $canDrop", 1, "https://pics.freeicons.io/uploads/icons/png/2271304231530177260-512.png");
    $Form->addButton("§eManage Members", 1, "https://cdn-icons-png.flaticon.com/128/3699/3699516.png");
    $Form->addButton("§eVisit Settings", 1, "https://pics.freeicons.io/uploads/icons/png/1873349951537856958-512.png");
    $Form->sendToPlayer($player);
  }
  public function Limit(Player $player)
  {
   $maxVisitors = $this->api->getMaxVisitors($player);
    $Form = new SimpleForm(
      function(Player $player, $Data): bool
      {
        if(is_null($Data))
        {
          return true;
        }
        
        switch($Data)
        {
          case 0:
          $maxVisitors = $this->api->getMaxVisitors($player);
          if($maxVisitors < 10)
          {
            if($this->api->setMaxVisitors($player, $maxVisitors + 1))
            {
              $player->sendMessage("§aSuccessfully updated max visitors to §e".$maxVisitors + 1);
            }else{
              $player->sendMessage("§cError can't update max visitors");
            }
          }
            break;
          case 1:
            $maxVisitors = $this->api->getMaxVisitors($player);
            if($maxVisitors > 1)
          {
            if($this->api->setMaxVisitors($player, $maxVisitors - 1))
            {
              $player->sendMessage("§aSuccessfully updated max visitors to §e".$maxVisitors - 1);
            }else{
              $player->sendMessage("§cError can't update max visitors");
            }
          }
            break;
        }
        return true;
      }
    );
    $Form->setTitle("§bReset Island");
    $Form->setContent("§6Limit : §e$maxVisitors" );
    $Form->addButton("§e+ Visters");
    $Form->addButton("§e- Visters");
    $Form->sendToPlayer($player);
  }
}
