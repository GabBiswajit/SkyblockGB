<?php

/**
*  █████╗ ██████╗ ██╗
* ██╔══██╗██╔══██╗██║
* ███████║██████╔╝██║
* ██╔══██║██╔═══╝ ██║
* ██║  ██║██║     ██║
* ╚═╝  ╚═╝╚═╝     ╚═╝
                   
*/

namespace Biswajit\Skyblock;

use pocketmine\Server;
use pocketmine\player\Player;

use Biswajit\Skyblock\Skyblock;
use Biswajit\Skyblock\EventHandler;

use pocketmine\item\Item;
use pocketmine\block\VanillaBlocks;
use pocketmine\item\VanillaItems;
use poketmine\world\World;
use muqsit\invmenu\InvMenu;
use pocketmine\block\Block;
use pocketmine\math\Facing;
use pocketmine\math\Vector3;
use pocketmine\utils\Config;
use pocketmine\entity\Entity;
use pocketmine\world\Position;
use pocketmine\entity\Location;
use Biswajit\Skyblock\api\PlayerInfoAPI;
use Biswajit\Skyblock\api\VariablesAPI;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\block\BlockTypeIds;
use pocketmine\scheduler\ClosureTask;
use pocketmine\entity\effect\EffectInstance;
use pocketmine\block\inventory\ChestInventory;
use pocketmine\entity\effect\StringToEffectParser;
use pocketmine\item\enchantment\EnchantmentInstance;
use pocketmine\network\mcpe\protocol\SetScorePacket;
use pocketmine\network\mcpe\protocol\PlayPacket;
use pocketmine\network\mcpe\protocol\AddPlayerPacket;
use pocketmine\network\mcpe\protocol\RemoveActorPacket;
use pocketmine\item\enchantment\StringToEnchantmentParser;
use pocketmine\network\mcpe\protocol\RemoveObjectivePacket;
use pocketmine\network\mcpe\protocol\types\ScorePacketEntry;
use pocketmine\network\mcpe\protocol\AdventureSettingsPacket;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStack;
use pocketmine\network\mcpe\protocol\SetDisplayObjectivePacket;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataFlags;
use pocketmine\network\mcpe\protocol\types\inventory\ItemStackWrapper;
use pocketmine\network\mcpe\protocol\types\entity\LongMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\FloatMetadataProperty;
use pocketmine\network\mcpe\protocol\types\entity\EntityMetadataProperties;

class API
{
  
  /** @var API */
  private static $instance;
  
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
    $this->config = $this->getSource()->getConfigFile();
  }
  
  /**
   * @return API
   */
  public static function getInstance(): API
  {
    return self::$instance;
  }
 
  /**
   * @return VariablesAPI
   */
  public static function getVariables(): VariablesAPI
  {
    $Variables = VariablesAPI::getInstance();
    return $Variables;
  }
  
  /**
   * @return PlayerInfoAPI
   */
  public static function getPlayerInfo($player): PlayerInfoAPI
  {
    $Info = new PlayerInfoAPI($player);
    return $Info;
  }
  
  public function registerPlayer($player): void
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if(!$this->hasSkyblock($playerName))
    {
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("NameTag", $playerName);
      $playerFile->setNested("Island", $playerName);
      $playerFile->setNested("Level.IslandLevel", 1);
      $playerFile->setNested("Co-Op.Members", []);
      $playerFile->setNested("Co-Op.MaxMembers", 5);
      $playerFile->setNested("Co-Op.Role", "Owner");
      $playerFile->setNested("IslandSettings.Locked", false);
      $playerFile->setNested("IslandSettings.FriendsVisit", false);
      $playerFile->setNested("IslandSettings.MaxVisitors", 5);
      $playerFile->setNested("IslandSettings.CanDropItems", true);
      $playerFile->save();
    }
  }
  
  public function createIsland($player): void 
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $worldPath = $this->getSource()->getDataFolder() . $this->config->get("IslandWorld"); 
    if(file_exists($worldPath))
    {
      if(!is_dir("worlds/$playerName"))
      {
        $zip = new \ZipArchive();
        $zip->open($worldPath);
        mkdir(Server::getInstance()->getDataPath() . "worlds/$playerName");
        $zip->extractTo(Server::getInstance()->getDataPath() . "worlds/$playerName");
        $zip->close();
        
        Server::getInstance()->getWorldManager()->loadWorld($playerName);
        $world = Server::getInstance()->getWorldManager()->getWorldByName($playerName);
        Server::getInstance()->getWorldManager()->unloadWorld($world); //Reloading The World
        Server::getInstance()->getWorldManager()->loadWorld($playerName);
        
        Server::getInstance()->getWorldManager()->loadWorld($playerName);     
      }
    }
  }
  
  public function teleportToIsland(Player $player, int $delay): void
  {
    $playerName = $player->getName();
    $this->getSource()->getScheduler()->scheduleDelayedTask(new ClosureTask(
      function () use ($player, $playerName): void
      {
        if($this->hasSkyblock($playerName))
        {
          if(!empty($this->getSource()->getPlayerFile($playerName)->get("Island")))
          {
            Server::getInstance()->getWorldManager()->loadWorld($this->getSource()->getPlayerFile($playerName)->get("Island"));
            $world = Server::getInstance()->getWorldManager()->getWorldByName($this->getSource()->getPlayerFile($playerName)->get("Island"));
            if(!is_null($world))
            {
              if($player->isOnline())
              {
                $player->teleport($world->getSpawnLocation());
              }
            }
          }
        }
      }
    ), $delay);
  }
  
  public function isLocked($player): bool 
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyblock($playerName))
    {
      if($this->getSource()->getPlayerFile($playerName)->getNested("IslandSettings.Locked"))
      {
        return true;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function lockIsland($player): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyblock($playerName))
    {
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("IslandSettings.Locked", true);
      $playerFile->save();
      return true;
    }else{
      return false;
    }
  }
 
  
  public function unlockIsland($player): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyblock($playerName))
    {
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("IslandSettings.Locked", false);
      $playerFile->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function getMaxVisitors($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyblock($playerName))
    {
      $data = $this->getSource()->getPlayerFile($playerName)->getNested("IslandSettings.MaxVisitors");
      return $data;
    }else{
      return null;
    }
  }
  
  public function setMaxVisitors($player, int $visitors): bool 
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyblock($playerName))
    {
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("IslandSettings.MaxVisitors", $visitors);
      $playerFile->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function setIslandSpawn($player): bool 
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyblock($playerName))
    {
      if($player->getLocation()->getWorld()->getFolderName() === $this->getSource()->getPlayerFile($playerName)->get("Island"))
      {
        $player->getWorld()->setSpawnLocation(new Vector3($player->getLocation()->x, $player->getLocation()->y, $player->getLocation()->z));
        return true;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function getCanDropItems($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyblock($playerName))
    {
      $data = $this->getSource()->getPlayerFile($playerName)->getNested("IslandSettings.CanDropItems");
      return $data;
    }else{
      return null;
    }
  }
  
  public function setCanDropItems($player, bool $canDrop): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyblock($playerName))
    {
      $playerFile = $this->getSource()->getPlayerFile($playerName);
      $playerFile->setNested("IslandSettings.CanDropItems", $canDrop);
      $playerFile->save();
      return true;
    }else{
      return false;
    }
  }
 
  public function CoOpPromote(string $victimName): bool
  {
    $role = $this->getCoOpRole($victimName);
    if($role !== "Owner" && $role !== "Co-Owner")
    {
      $role = $this->getCoOpRole($victimName);
      $promotedRole = array(
        "Builder" => "Member",
        "Member" => "Senior-Member",
        "Senior-Member" => "Co-Owner"
        );
      $new_role = $promotedRole[$role];
      $file = $this->getSource()->getPlayerFile($victimName);
      $file->setNested("Co-Op.Role", $new_role);
      $file->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function CoOpDemote(string $victimName)
  {
    $role = $this->getCoOpRole($victimName);
    if($role !== "Owner" && $role !== "Builder")
    {
      $role = $this->getCoOpRole($victimName);
      $demotedRole = array(
        "Member" => "Builder",
        "Senior-Member" => "Member",
        "Co-Owner" => "Senior-Member"
        );
      $new_role = $demotedRole[$role];
      $file = $this->getSource()->getPlayerFile($victimName);
      $file->setNested("Co-Op.Role", $new_role);
      $file->save();
      return true;
    }else{
      return false;
    }
  }
  
  public function addCoOp($player, string $victimName): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $victimRealName = $this->getRealCoOpName($player, $victimName);
    $hasRequest = $this->hasCoOpRequest($playerName, $victimName);
    $isCoOp = $this->isCoOp($playerName, $victimRealName);
    if($this->hasSkyblock($playerName))
    {
      if($hasRequest && !$isCoOp)
      {
        if($victimRealName !== "" && $playerName !== $victimRealName)
        {
          $members = count($this->getMembers($player));
          $maxMembers = $this->getMaxMembers($player);
          if($members < $maxMembers)
          {
            $this->removeCoOpRequest($playerName, $victimRealName);
            $playerFile = $this->getSource()->getPlayerFile($playerName);
            $victimFile = $this->getSource()->getPlayerFile($victimRealName);
            $playerFile->setNested("Island", $victimFile->get("Island"));
            $playerFile->setNested("Co-Op.Role", "Member");
            $playerFile->save();
            $world = $this->getSource()->getServer()->getWorldManager()->getWorldByName($playerName);
            $this->getSource()->getServer()->getWorldManager()->unloadWorld($world);
            $this->deleteDirectory($this->getSource()->getServer()->getDataPath() . "worlds/$playerName");
            $victim = Server::getInstance()->getPlayerByPrefix($playerName);
            if($victim instanceof Player)
            {
              $this->teleportToIsland($victim, 1);
            }
            foreach(scandir($this->getSource()->getDataFolder() . "players") as $key => $file)
            {
              if(is_file($this->getSource()->getDataFolder() . "players/$file"))
              {
                $playerFile = new Config($this->getSource()->getDataFolder() . "players/$file", Config::YAML, [
                  ]);
                if($playerFile->get("Island") === $victimFile->get("Island"))
                {
                  if($playerFile->getNested("Co-Op.Role") === "Owner" || $playerFile->getNested("Co-Op.Role") === "Co-Owner")
                  {
                    $members = $playerFile->getNested("Co-Op.Members");
                    $members[] = $playerName;
                    $playerFile->setNested("Co-Op.Members", $members);
                    $playerFile->save();
                  }
                }
              }
            }
            return true;
          }else{
            return false;
          }
        }else{
          return false;
        }
      }else{
        return false;
      }
    }else{
      return false;
    }
  }

  public function removeCoOp(string $victimName): bool
  {
    if($this->hasSkyblock($victimName))
    {
      if($victimName !== "")
      {
        $victimFile = $this->getSource()->getPlayerFile($victimName);
        foreach(scandir($this->getSource()->getDataFolder() . "players") as $key => $file)
        {
          if(is_file($this->getSource()->getDataFolder() . "players/$file"))
          {
            $playerFile = new Config($this->getSource()->getDataFolder() . "players/$file", Config::YAML, [
              ]);
            if($playerFile->get("Island") === $victimFile->get("Island"))
            {
              if($playerFile->getNested("Co-Op.Role") === "Owner" || $playerFile->getNested("Co-Op.Role") === "Co-Owner")
              {
                $members = $playerFile->getNested("Co-Op.Members");
                $new_Members = $this->removeKeyFromArray($members, $victimName);
                $playerFile->setNested("Co-Op.Members", $new_Members);
                $playerFile->save();
              }
            }
          }
        }
        $victimFile->setNested("Island", "$victimName");
        $victimFile->setNested("Co-Op.Role", "Owner");
        $victimFile->setNested("Co-Op.Members", []);
        $victimFile->save();
        $this->createIsland($victimName);
        return true;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
 
  public function addCoOpRequest($player, string $victimName): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $hasRequest = $this->hasCoOpRequest($playerName, $victimName);
    if(!$hasRequest)
    {
      if($playerName !== $victimName)
      {
        $requests = [];
        $requests = $this->getCoOpRequests($playerName);
        $requests[] = $victimName;
        $playerFile = $this->getSource()->getPlayerFile($playerName);
        $playerFile->setNested("Co-Op.Requests", $requests);
        $playerFile->save();
        $this->getSource()->getScheduler()->scheduleDelayedTask(new ClosureTask(
          function () use ($playerName, $victimName): void 
          {
            $hasRequest = $this->hasCoOpRequest($playerName, $victimName);
            if($hasRequest && $playerName !== $victimName)
            {
              $this->removeCoOpRequest($playerName, $victimName);
              $victim = Server::getInstance()->getPlayerExact($victimName);
              if($victim instanceof Player)
              {
                $victim->sendMessage("§cyour co-op request to §e$playerName §chas expired");
              }
            }
          }
        ), 20 * 60);
        return true;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function removeCoOpRequest($player, string $victimName): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $hasRequest = $this->hasCoOpRequest($playerName, $victimName);
    if($this->hasSkyblock($playerName))
    {
      if($hasRequest)
      {
        if(count($this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Requests")) > 1)
        {
          $victimRealName = $this->getRealTradeName($playerName, $victimName);
          if($victimRealName !== "" && $playerName !== $victimRealName)
          {
            $array = $this->getTradeRequests($playerName);
            $requests = $this->removeKeyFromArray($array, $victimRealName);
            $playerFile = $this->getSource()->getPlayerFile($playerName);
            $playerFile->setNested("Co-Op.Requests", $requests);
            $playerFile->save();
            return true;
          }else{
            return false;
          }
        }else{
          $victimRealName = $this->getRealCoOpName($playerName, $victimName);
          if($victimRealName !== "" && $playerName !== $victimRealName)
          {
            $requests = $this->getCoOpRequests($playerName);
            if($requests[0] === $victimRealName)
            {
              $playerFile = $this->getSource()->getPlayerFile($playerName);
              $playerFile->removeNested("Co-Op.Requests");
              $playerFile->save();
              return true;
            }else{
              return false;
            }
          }else{
            return false;
          }
        }
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
 
  public function getRealCoOpName($player, string $victimName)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyblock($playerName))
    {
      if(!is_null($this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Requests")))
      {
        $realName = "";
        if(is_array($this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Requests")))
        {
          foreach($this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Requests") as $request)
          {
            $name = strtolower($victimName);
            $delta = PHP_INT_MAX;
            if(stripos($request, $name) === 0)
            {
              $curDelta = strlen($request) - strlen($name);
              if($curDelta < $delta)
              {
                $realName = $request;
                $delta = $curDelta;
              }
              if($curDelta === 0)
              {
                break;
              }
            }
          }
        }
        return $realName;
      }else{
        return "";
      }
    }else{
      return "";
    }
  }
  
  public function hasCoOpRequest($player, string $victimName): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyblock($playerName))
    {
      if(!is_null($this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Requests")))
      {
        $hasRequest = false;
        if(is_array($this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Requests")))
        {
          foreach($this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Requests") as $request)
          {
            $name = strtolower($victimName);
            $delta = PHP_INT_MAX;
            if(stripos($request, $name) === 0)
            {
              $curDelta = strlen($request) - strlen($name);
              if($curDelta < $delta)
              {
                $hasRequest = true;
                $delta = $curDelta;
              }
              if($curDelta === 0)
              {
                break;
              }
            }
          }
        }
        return $hasRequest;
      }else{
        return false;
      }
    }else{
      return false;
    }
  }
  
  public function isCoOp($player, string $victimName): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $a_Island = $this->getSource()->getPlayerFile($playerName)->get("Island");
    $b_Island = $this->getSource()->getPlayerFile($victimName)->get("Island");
    if($a_Island !== $b_Island)
    {
      return false;
    }else{
      return true;
    }
  }
  
  public function getCoOpRequests($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyblock($playerName))
    {
      if(!is_null($this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Requests")))
      {
        $requests = [];
        if(is_array($this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Requests")))
        {
          foreach($this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Requests") as $request)
          {
            $requests[] = $request;
          }
        }
        return $requests;
      }else{
        return [];
      }
    }else{
      return [];
    }
  }
 
  public function getCoOpRolePerm(string $role)
  {
    switch($role)
    {
      case "Co-Owner":
        $array = array(
          "Build",
          "Interact"
          );
        return $array;
      case "Senior-Member":
        $array = array(
          "Build",
          "Interact"
          );
        return $array;
        break;
      case "Member":
        $array = array(
          "Build",
          "Interact"
          );
        return $array;
        break;
      case "Builder":
        $array = array(
          "Build"
          );
        return $array;
        break;
      default:
        return array();
        break;
    }
  }
  
  public function hasCoOpPerm($player, string $perm)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    $role = $this->getCoOpRole($playerName);
    $perms = $this->getCoOpRolePerm($role);
    if($role !== "Owner" && $role !== "Co-Owner")
    {
      if(in_array($perm, $perms))
      {
        return true;
      }else{
        return false;
      }
    }else{
      return true;
    }
  }
 
  public function getCoOpRole($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyblock($playerName))
    {
      $role = $this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Role");
      return $role;
    }else{
      return null;
    }
  }
  
  public function getMaxMembers($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyblock($playerName))
    {
      $maxMembers = $this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.MaxMembers");
      return $maxMembers;
    }else{
      return 5;
    }
  }
  
  public function getMembers($player)
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if($this->hasSkyblock($playerName))
    {
      $members = $this->getSource()->getPlayerFile($playerName)->getNested("Co-Op.Members");
      return $members;
    }else{
      return 0;
    }
  }
  
  public function removeKeyFromArray(array $a_array, $a_key): array
  {
    $b_array = [];
    foreach($a_array as $b_key)
    {
      if($a_key !== $b_key)
      {
        $b_array[] = $b_key;
      }
    }
    return $b_array;
  }
  
  public function removeItemFromDrops(array $a_array, Item $a_item): array
  {
    $b_array = [];
    foreach($a_array as $b_item)
    {
      if($b_item->getId() !== $a_item->getId() && $a_item->getMeta() !== $b_item->getMeta())
      {
        $b_array[] = $b_item;
      }
    }
    return $b_array;
  }
  
  public function deleteDirectory($dirPath)
  {
    if(is_dir($dirPath))
    {
      $objects = scandir($dirPath);
      foreach($objects as $object)
      {
        if($object != "." && $object !="..")
        {
          if(filetype($dirPath . DIRECTORY_SEPARATOR . $object) == "dir")
          {
            $this->deleteDirectory($dirPath . DIRECTORY_SEPARATOR . $object);
          }else{
            unlink($dirPath . DIRECTORY_SEPARATOR . $object);
          }
        }
      }
    reset($objects);
    rmdir($dirPath);
    }
  }
  
  public function randomString(int $length = 40, $path = "", string $extension = "yml")
  {
    $key = '';
    $keys = array_merge(range(0, 9), range('a', 'z'));
    for ($i = 0; $i < $length; $i++)
    {
        $key .= $keys[array_rand($keys)];
    }
    if(is_array($path))
    {
      if(!array_key_exists($key, $path))
      {
        return $key;
      }else{
        return $this->randomString($length, $path, $extension);
      }
    }elseif($path === "" || !file_exists($path . "/$key" . "." . $extension))
    {
      return $key;
    }else{
      return $this->randomString($length, $path, $extension);
    }
  }
  
  public static function getSource(): Skyblock
  {
    $Skyblock = Skyblock::getInstance();
    return $Skyblock;
  }
  
  public function hasSkyblock($player): bool
  {
    if($player instanceof Player)
    {
      $playerName = $player->getName();
    }else{
      $playerName = $player;
    }
    if(file_exists($this->getSource()->getDataFolder() . "players/$playerName" . ".yml"))
    {
      return true;
    }else{
      return false;
    }
  }
  
}
