<?php

/** 
 * 
 * ███████╗██╗   ██╗███████╗███╗  ██╗████████╗██╗  ██╗ █████╗ ███╗  ██╗██████╗░██╗     ███████╗██████╗ 
 * ██╔════╝██║   ██║██╔════╝████╗ ██║╚══██╔══╝██║  ██║██╔══██╗████╗ ██║██╔══██╗██║     ██╔════╝██╔══██╗
 * █████╗  ╚██╗ ██╔╝█████╗  ██╔██╗██║   ██║   ███████║███████║██╔██╗██║██║  ██║██║     █████╗  ██████╔╝
 * ██╔══╝   ╚████╔╝ ██╔══╝  ██║╚████║   ██║   ██╔══██║██╔══██║██║╚████║██║  ██║██║     ██╔══╝  ██╔══██╗
 * ███████╗  ╚██╔╝  ███████╗██║ ╚███║   ██║   ██║  ██║██║  ██║██║ ╚███║██████╔╝███████╗███████╗██║  ██║
 * ╚══════╝   ╚═╝   ╚══════╝╚═╝  ╚══╝   ╚═╝   ╚═╝  ╚═╝╚═╝  ╚═╝╚═╝  ╚══╝╚═════╝ ╚══════╝╚══════╝╚═╝  ╚═╝
 */
namespace Biswajit\Skyblock;

use pocketmine\Server;
use pocketmine\player\Player;

use Biswajit\Skyblock\API;
use Biswajit\Skyblock\Skyblock;
use pocketmine\event\Listener;
use pocketmine\item\ItemBlock;
use pocketmine\item\VanillaItems;

use pocketmine\block\Block;
use pocketmine\block\VanillaBlocks;
use pocketmine\block\BlockTypeIds;

use pocketmine\item\Armor;
use pocketmine\math\Facing;
use pocketmine\math\Vector2;
use pocketmine\math\Vector3;
use pocketmine\entity\Living;
use pocketmine\world\Position;
use pocketmine\entity\Location;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\scheduler\ClosureTask;


use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\block\BlockUpdateEvent;
use pocketmine\event\block\LeavesDecayEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\world\ChunkUnloadEvent;
use pocketmine\event\player\PlayerJumpEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\data\bedrock\EnchantmentIdMap;
use pocketmine\event\entity\EntitySpawnEvent;
use pocketmine\event\inventory\CraftItemEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\entity\EntityItemPickupEvent;
use pocketmine\event\player\PlayerBucketFillEvent;
use pocketmine\event\player\PlayerBucketEmptyEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\event\player\PlayerToggleSneakEvent;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityTrampleFarmlandEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;

class EventHandler implements Listener
{
 
  
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
    $this->api = $source->getAPI();
    $this->config = $source->getConfigFile();
  }
  
  public static function getInstance(): EventHandler
  {
    return self::$instance;
  }
  
  public function onJoin(PlayerJoinEvent $event)
  {
    $player = $event->getPlayer();
    $playerName = $player->getName();
    
    if($this->getSource()->getConfigFile()->getNested("Join.CreateIsland"))
    {
      if(!$this->api->hasSkyblock($player))
      {
        $this->api->registerPlayer($player);
        $this->api->createIsland($player);
      }
    }
    if($this->getSource()->getConfigFile()->getNested("Join.TeleportToIsland"))
    {
      $this->api->teleportToIsland($player, 20);
    }
  }
 
  public function onPickupItem(EntityItemPickupEvent $event)
  {
    $player = $event->getEntity();
    $playerName = $player->getName();
    if($player->getWorld()->getFolderName() !== $this->getSource()->getPlayerFile($playerName)->get("Island")) {
      $worldName = $player->getWorld()->getFolderName();
      if($this->api->hasSkyblock($worldName) && $worldName !== Server::getInstance()->getWorldManager()->getDefaultWorld()->getFolderName())
      {
        if($worldName !== $this->getSource()->getPlayerFile($worldName)->get("Island"))
        {
          if($this->getSource()->getPlayerFile($worldName)->getNested("IslandSettings.CanDropItems"))
          {
            $event->uncancel();
          }else{
            $event->cancel();
          }
        }else{
          $event->uncancel();
        }
      }else{
        $event->uncancel();
      }
    }else{
        $event->uncancel();
    }
  }
 
  public function onInteract(PlayerInteractEvent $event)
  {
    $player = $event->getPlayer();
    $playerName = $player->getName();
    $block = $event->getBlock();
    if($player->getWorld()->getFolderName() === $this->getSource()->getPlayerFile($playerName)->get("Island"))
    {
      $role = $this->api->getCoOpRole($player);
      if($this->api->getCoOpRole($player) === "Owner" || $this->api->getCoOpRole($player) === "Co-Owner" || $this->api->hasCoOpPerm($player, "Interact"))
      {
        $event->uncancel();
      }else{
        $event->cancel();
      }
    }
  }
 
  public function onBreak(BlockBreakEvent $event)
  {
    
    $block = $event->getBlock();
    $player = $event->getPlayer();
    $playerName = $player->getName();
    if($player->getWorld()->getFolderName() === $this->getSource()->getPlayerFile($playerName)->get("Island"))
    {
      if($this->api->getCoOpRole($player) === "Owner" || $this->api->getCoOpRole($player) === "Co-Owner" || $this->api->hasCoOpPerm($player, "Interact"))
      {
        $event->uncancel();
      }else{
        $event->cancel();
        }
      }
    }
  
  public function onPlace(BlockPlaceEvent $event)
  {
    $block = $event->getBlock();
    $player = $event->getPlayer();
    $playerName = $player->getName();
    
    if($player->getWorld()->getFolderName() === $this->getSource()->getPlayerFile($playerName)->get("Island"))
    {
      if($this->api->getCoOpRole($player) === "Owner" || $this->api->getCoOpRole($player) === "Co-Owner" || $this->api->hasCoOpPerm($player, "Build"))
      {
        $event->uncancel();
      }else{
        $event->cancel();
      }
    }
  }
    
  public function onDropItem(PlayerDropItemEvent $event)
  {
    $item = $event->getItem();
    $player = $event->getPlayer();
    $playerName = $player->getName();
    if($player->getLocation()->world->getFolderName() !== $this->getSource()->getPlayerFile($playerName)->get("Island") && $player->getLocation()->world->getFolderName() !== Server::getInstance()->getWorldManager()->getDefaultWorld()->getFolderName())
    {
      $worldName = $player->getWorld()->getFolderName();
      if($this->api->hasSkyblock($worldName) && $worldName !== Server::getInstance()->getWorldManager()->getDefaultWorld()->getFolderName())
      {
        if($worldName !== $this->getSource()->getPlayerFile($worldName)->get("Island"))
        {
          if($this->getSource()->getPlayerFile($worldName)->getNested("IslandSettings.CanDropItems"))
          {
            $event->uncancel();
          }else{
            $event->cancel();
          }
        }else{
          $event->uncancel();
        }
      }else{
        $event->uncancel();
      }
    }else{
      $event->uncancel();
    }
  }
 
  /**
   * @return Skyblock
   */
  public function getSource(): Skyblock
  {
    $Skyblock = API::getSource();
    return $Skyblock;
  }
}
