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
        $result = (string) $data[0];
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
            $GUI = $this->api->getSource()->getGUI();
            $GUI->SettingsMenu($player);
            break;
          case 1:
            $GUI = $this->api->getSource()->getGUI();
            $GUI->VisitMenu($player);
            break;
          case 2:
            $this->Lock($player);
            break;
          case 3:
               $playerName = $player->getName();
               $playerFile = $this->api->getSource()->getPlayerFile($playerName);
               $this->api->getSource()->getServer()->getWorldManager()->loadWorld($playerFile->get("Island"));
               $world = $this->api->getSource()->getServer()->getWorldManager()->getWorldByName($playerFile->get("Island"));
               $this->api->getSource()->getServer()->getWorldManager()->unloadWorld($world);
               $this->api->deleteDirectory(Server::getInstance()->getDataPath() . "worlds/" . $playerFile->get("Island"));
               $this->api->createIsland($playerName);
            break;
        }
        return true;
      }
    );
    $Form->setTitle("§l§cSKYBLOCK SETTINGS");
    $Form->addButton("§eIsland Settings", 1, "https://icons.iconarchive.com/icons/dtafalonso/android-lollipop/256/Settings-icon.png");
    $Form->addButton("§eVisit Island", 0, "textures/items/ender_pearl");
    $Form->addButton("§eVisit Settings", 1, "https://www.clipartmax.com/png/full/162-1624622_brenz-block-is-a-skyblock-minecraft-logo.png");
    $Form->addButton("§eReset Island", 1, "https://www.clipartmax.com/png/full/162-1624622_brenz-block-is-a-skyblock-minecraft-logo.png");
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
}
