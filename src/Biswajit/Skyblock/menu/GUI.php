<?php

namespace Biswajit\Skyblock\menu;

use pocketmine\Server;
use pocketmine\player\Player;
use Biswajit\Skyblock\API;
use Biswajit\Skyblock\Skyblock;
use Biswajit\Skyblock\EventHandler;
use pocketmine\block\utils\DyeColor;

use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\item\VanillaItems;
use pocketmine\item\ItemTypeIds;
use pocketmine\block\VanillaBlocks;

use muqsit\invmenu\InvMenu;
use pocketmine\world\format\io\GlobalItemDataHandlers;
use pocketmine\inventory\SimpleInventory;
use pocketmine\data\bedrock\EnchantmentIdMap;
use muqsit\invmenu\transaction\InvMenuTransaction;
use muqsit\invmenu\transaction\InvMenuTransactionResult;

use pocketmine\color\Color;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\ClosureTask;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\item\enchantment\StringToEnchantmentParser;

class GUI
{
  
  /** @var InvMenu */
  private $DoubleChest;
  
  /** @var InvMenu */
  private $SingleChest;
  
  /** @var String */
  private $Window;
  
  /** @var bool */
  private $ItemsReturned;
  
  /** @var array */
  private $Listings;
  
  /** @var Instance */
  private static $instance;
  
  /** @var API */
  public $api;
  
  /** @var Skyblock */
  private $source;
  
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
    $this->DoubleChest = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
    $this->SingleChest = InvMenu::create(InvMenu::TYPE_CHEST);
    $this->Window = "";
  }
  
  public static function getInstance(): GUI
  {
    return self::$instance;
  }

  public function VisitMenu(Player $player): void
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bSky§3block");
    $menu->setListener(
      function (InvMenuTransaction $transaction) use ($menu) : InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $item = $transaction->getItemClicked();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getTypeId();
        $itemOutId = $transaction->getOut()->getTypeId();
        $inv = $transaction->getAction()->getInventory();
        $playerName = $transaction->getPlayer()->getName();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
        if($item->getTypeId() === ItemTypeIds::fromBlockTypeId(VanillaBlocks::MOB_HEAD()->getTypeId()))
        {
          $visitingPlayer = Server::getInstance()->getPlayerExact(str_replace(["§r §b", " §r"], ["", ""], $itemOut->getCustomName()));
          if(!is_null($visitingPlayer))
          {
          $visitingPlayerName = $visitingPlayer->getName();
          if($visitingPlayer instanceof Player)
          {
            if($this->api->hasSkyblock($visitingPlayerName))
            {
              $worldName = $this->source->getInstance()->getPlayerFile($visitingPlayerName)->get("Island");
              if(!is_null($worldName))
              {
                Server::getInstance()->getWorldManager()->loadWorld($worldName);
                $world = Server::getInstance()->getWorldManager()->getWorldByName($worldName);
                if($world !== null)
                {
                  if($world->getFolderName() !== $player->getLocation()->world->getFolderName())
                  {
                    if(!$this->source->getInstance()->getPlayerFile($visitingPlayerName)->getNested("IslandSettings.Locked"))
                    {
                      if(count($world->getPlayers()) < $this->source->getInstance()->getPlayerFile($visitingPlayerName)->getNested("IslandSettings.MaxVisitors"))
                      {
                        $player->teleport($world->getSpawnLocation());
                        $player->sendMessage("§aVisiting §e$visitingPlayerName");
                      }else{
                        $player->sendMessage("§cmaximum number of visitors reached");
                      }
                      }
                        if(count($world->getPlayers()) < $this->source->getInstance()->getPlayerFile($visitingPlayerName)->getNested("IslandSettings.MaxVisitors"))
                        {
                          $player->teleport($world->getSpawnLocation());
                          $player->sendMessage("§aVisiting §e$visitingPlayerName");
                        }else{
                          $player->sendMessage("§cMaximum number of visitor reached");
                        }
                      }else{
                        $player->sendMessage("§cIsland is locked");
                      }
                    }else{
                      $player->sendMessage("§cIsland is locked");
                    }
                  }else{
                    $player->sendMessage("§can error occurred");
                  }
                }else{
                  $player->sendMessage("§can error occurred");
                }
              }
            }else{
              $player->sendMessage("§can error occurred");
             }
        }elseif($transaction->getAction()->getSlot() === 49)
        {
          $player->removeCurrentWindow();
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(1, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(2, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(3, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(4, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(5, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(6, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(7, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(8, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(9, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(10, VanillaItems::AIR());
    $inv->setItem(11, VanillaItems::AIR());
    $inv->setItem(12, VanillaItems::AIR());
    $inv->setItem(13, VanillaItems::AIR());
    $inv->setItem(14, VanillaItems::AIR());
    $inv->setItem(15, VanillaItems::AIR());
    $inv->setItem(16, VanillaItems::AIR());
    $inv->setItem(17, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(18, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(19, VanillaItems::AIR());
    $inv->setItem(20, VanillaItems::AIR());
    $inv->setItem(21, VanillaItems::AIR());
    $inv->setItem(22, VanillaItems::AIR());
    $inv->setItem(23, VanillaItems::AIR());
    $inv->setItem(24, VanillaItems::AIR());
    $inv->setItem(25, VanillaItems::AIR());
    $inv->setItem(26, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(27, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(28, VanillaItems::AIR());
    $inv->setItem(29, VanillaItems::AIR());
    $inv->setItem(30, VanillaItems::AIR());
    $inv->setItem(31, VanillaItems::AIR());
    $inv->setItem(32, VanillaItems::AIR());
    $inv->setItem(33, VanillaItems::AIR());
    $inv->setItem(34, VanillaItems::AIR());
    $inv->setItem(35, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(36, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(37, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(38, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(39, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(40, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(41, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(42, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(43, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(44, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(45, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(46, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(47, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(48, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(49, VanillaBlocks::REDSTONE()->asItem()->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(51, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(52, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(53, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $i = 0;
    foreach(Server::getInstance()->getOnlinePlayers() as $online)
    {
      if($player->getName() !== $online->getName())
      {
        if($i < 7)
        {
          $slot = $i + 10;
          $playerName = $online->getName();
          $inv->setItem($slot, VanillaBlocks::MOB_HEAD()->asItem()->setCustomName("§r §b$playerName §r"));
        }elseif($i < 14)
        {
          $slot = $i + 12;
          $playerName = $online->getName();
          $inv->setItem($slot, VanillaBlocks::MOB_HEAD()->asItem()->setCustomName("§r §b$playerName §r"));
        }elseif($i < 21)
        {
          $slot = $i + 14;
          $playerName = $online->getName();
          $inv->setItem($slot, VanillaBlocks::MOB_HEAD()->asItem()->setCustomName("§r §b$playerName §r"));
        }
        $i++;
      }
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
  
  public function ManageMembersMenu(Player $player)
  {
    $menu = $this->DoubleChest;
    $menu->setName("§bMembers §3List");
    $menu->setListener(
      function (InvMenuTransaction $transaction): InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $item = $transaction->getItemClicked();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getTypeId();
        $itemOutId = $transaction->getOut()->getTypeId();
        $inv = $transaction->getAction()->getInventory();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
         if($transaction->getAction()->getSlot() === 49)
        {
          $player->removeCurrentWindow();
        }elseif($item->getTypeId() === ItemTypeIds::fromBlockTypeId(VanillaBlocks::MOB_HEAD()->getTypeId()))
        {
          $member = str_replace(["§r §e", " §r"], ["", ""], $itemOutName);
          $this->ManageMemberMenu($player, $member);
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(1, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(2, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(3, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(4, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(5, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(6, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(7, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(8, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(9, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(10, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(11, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(12, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(13, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(14, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(15, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(16, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(17, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(18, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(19, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(20, VanillaItems::AIR());
    $inv->setItem(21, VanillaItems::AIR());
    $inv->setItem(22, VanillaItems::AIR());
    $inv->setItem(23, VanillaItems::AIR());
    $inv->setItem(24, VanillaItems::AIR());
    $inv->setItem(25, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(26, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(27, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(28, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(29, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(30, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(31, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(32, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(33, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(34, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(35, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(36, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(37, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(38, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(39, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(40, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(41, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(42, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(43, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(44, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(45, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(46, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(47, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(48, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(49, VanillaBlocks::REDSTONE()->asItem()->setCustomName("§r §cExit §r\n§r §7click to exit the menu §r"));
    $inv->setItem(50, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(51, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(52, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(53, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $i = 1;
    $island = $this->api->getSource()->getPlayerFile($player)->get("Island");
    $members = array();
    foreach(scandir($this->api->getSource()->getDataFolder() . "players") as $key => $file)
    {
      if(is_file($this->api->getSource()->getDataFolder() . "players/$file"))
      {
        $playerFile = new Config($this->api->getSource()->getDataFolder() . "players/$file", Config::YAML, [
          ]);
        if($playerFile->get("Island") === $island && ($playerFile->getNested("Co-Op.Role") === "Owner" || $playerFile->getNested("Co-Op.Role") === "Co-Owner"))
        {
          $members = $playerFile->getNested("Co-Op.Members");
        }
      }
    }
    foreach($members as $member)
    {
      $slot = $i + 19;
      $inv->setItem($slot, VanillaBlocks::MOB_HEAD()->asItem()->setCustomName("§r §e$member §r"));
    }
    if($this->Window !== "Double-Chest")
    {
      $menu->send($player);
      $this->Window = "Double-Chest";
    }
  }
    
  public function ManageMemberMenu(Player $player, string $member)
  {
    $menu = $this->SingleChest;
    $menu->setName("§Manage §3Member");
    $menu->setListener(
      function(InvMenuTransaction $transaction) use($member): InvMenuTransactionResult 
      {
        $itemIn = $transaction->getIn();
        $item = $transaction->getItemClicked();
        $itemOut = $transaction->getOut();
        $player = $transaction->getPlayer();
        $itemInId = $transaction->getIn()->getTypeId();
        $itemOutId = $transaction->getOut()->getTypeId();
        $inv = $transaction->getAction()->getInventory();
        $itemInName = $transaction->getIn()->getCustomName();
        $itemOutName = $transaction->getOut()->getCustomName();
        
         if($transaction->getAction()->getSlot() === 11)
          {
          if($player->getName() !== $member)
          {
            if($transaction->getAction()->getSlot() === 15)
            {
              if($this->api->CoOpPromote($member))
              {
                $role = $this->api->getCoOpRole($member);
                $player->sendMessage("§apromoted §e$member §ato §e$role");
                $player->removeCurrentWindow();
              }
            }elseif($transaction->getAction()->getSlot() === 11)
            {
              if($this->api->CoOpDemote($member))
              {
                $role = $this->api->getCoOpRole($member);
                $player->sendMessage("§ademoted §e$member §ato §e$role");
                $player->removeCurrentWindow();
              }
            }
          }
        }elseif($transaction->getAction()->getSlot() === 13)
        {
          if($player->getName() !== $member)
          {
            if($this->api->removeCoOp($member))
            {
              $player->sendMessage("§aremoved §e$member from Co-Op");
            }else{
              $player->sendMessage("§ccan't remove the player from Co-Op");
            }
            $player->removeCurrentWindow();
          }
        }
        
        return $transaction->discard();
      }
    );
    $inv = $menu->getInventory();
    $inv->setItem(0, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(1, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(2, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(3, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(4, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(5, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(6, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(7, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(8, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(9, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(10, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $demotedRole = array(
      "Builder" => "-",
      "Member" => "Builder",
      "Senior-Member" => "Member",
      "Co-Owner" => "Senior-Member",
      "Owner" => "-"
      );
    $demoted = $demotedRole[$this->api->getCoOpRole($member)];
    $inv->setItem(11, GlobalItemDataHandlers::getDeserializer()->deserializeStack(GlobalItemDataHandlers::getUpgrader()->upgradeItemTypeDataInt(35, 14, 1, null))->setCustomName("§r §cDemote §r\n§r §7 §r\n§r §7Demoted Role: §e$demoted §r"));
    $inv->setItem(12, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(13, GlobalItemDataHandlers::getDeserializer()->deserializeStack(GlobalItemDataHandlers::getUpgrader()->upgradeItemTypeDataInt(152, 0, 1, null))->setCustomName("§r §cRemove §r"));
    $inv->setItem(14, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $promotedRole = array(
      "Builder" => "Member",
      "Member" => "Senior-Member",
      "Senior-Member" => "Co-Owner",
      "Co-Owner" => "-",
      "Owner" => "-"
      );
    $promoted = $promotedRole[$this->api->getCoOpRole($member)];
    $inv->setItem(15, GlobalItemDataHandlers::getDeserializer()->deserializeStack(GlobalItemDataHandlers::getUpgrader()->upgradeItemTypeDataInt(35, 5, 1, null))->setCustomName("§r §aPromote §r\n§r §7 §r\n§r §7Prmoted Role: §e$promoted §r"));
    $inv->setItem(16, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(17, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(18, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(19, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(20, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(21, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(22, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(23, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(24, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(25, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    $inv->setItem(26, VanillaBlocks::GLASS_PANE()->asItem()->setCustomName("§r §7 §r"));
    if($this->Window !== "Single-Chest")
    {
      $menu->send($player);
      $this->Window = "Single-Chest";
    }
  }

}
