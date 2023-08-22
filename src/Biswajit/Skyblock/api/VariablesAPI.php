<?php

namespace Biswajit\Skyblock\api;

use pocketmine\Server;
use pocketmine\player\Player;

use Biswajit\Skyblock\Skyblock;

use muqsit\invmenu\InvMenu;

class VariablesAPI
{
  
  /** @var VariablesAPI */
  private static $instance;
  
  /** @var Array */
  private array $Window;
  
    /** @var InvMenu */
  private $DoubleChest;
  
  /** @var InvMenu */
  private $SingleChest;
  
  public function __construct()
  {
    self::$instance = $this;
    $this->SingleChest = InvMenu::create(InvMenu::TYPE_CHEST);
    $this->DoubleChest = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
    $this->Window = [];
  }
  
  /**
   * @return VariablesAPI
   */
  public static function getInstance(): VariablesAPI
  {
    return self::$instance;
  }
  
  
  /**
   * @param string $Type
   * @return Varible
   */
  public function getVariable(string $Type)
  {
    if($Type === "Window")
    {
      return $this->Window;
    }elseif($Type === "SingleChest-InvMenu")
    {
      return $this->SingleChest;
    }elseif($Type === "DoubleChest-InvMenu")
    {
      return $this->DoubleChest;
    }
  }
  
  /**
   * @param string $Type
   * @param int|array|string $Key
   * @return Bool
   */
  public function hasKey(string $Type, $Key)
  {
    $Variable = $this->getVariable($Type);
    
    if(is_array($Variable))
    {
      if(array_key_exists($Key, $Variable))
      {
        $Bool = true;
      }else{
        $Bool = false;
      }
    }else{
      $Bool = false;
    }
    
    return $Bool;
  }
  
  /**
   * @param string $Type
   * @param int|array|string $Key
   * @return Void
   */
  public function removeKey(string $Type, $Key)
  {
    $Variable = $this->getVariable($Type);
    
    if(is_array($Variable))
    {
      if(array_key_exists($Key, $Variable))
      {
        if($Type === "Window")
        {
          unset($this->Window[$Key]);
        }
      }
    }
  }
  
  /**
   * @return Skyblock
   */
  public function getSource(): Skyblock
  {
    return Skyblock::getInstance();
  }
  
}