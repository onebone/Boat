<?php

namespace onebone\boat;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\inventory\BigShapelessRecipe;
use pocketmine\item\Item;
use pocketmine\entity\Entity;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\protocol\InteractPacket;
use pocketmine\network\protocol\SetEntityLinkPacket;
use pocketmine\network\protocol\MovePlayerPacket;
use pocketmine\event\player\PlayerQuitEvent;

use onebone\boat\item\Boat as BoatItem;
use onebone\boat\packet\PlayerInputPacket;
use onebone\boat\entity\Boat;

class Main extends PluginBase implements Listener{
  private $riding = [];

  public function onEnable(){
    $this->getServer()->getPluginManager()->registerEvents($this, $this);

    Item::$list[333] = BoatItem::class;
    Item::addCreativeItem(new Item(333));
    $this->getServer()->addRecipe((new BigShapelessRecipe(Item::get(333, 0, 1)))->addIngredient(Item::get(Item::WOODEN_PLANK, null, 5))->addIngredient(Item::get(Item::WOODEN_SHOVEL, null, 1)));

    Entity::registerEntity("\\onebone\\boat\\entity\\Boat", true);

    $this->getServer()->getNetwork()->registerPacket(0xae, PlayerInputPacket::class);
  }

  public function onQuit(PlayerQuitEvent $event){
    if(isset($this->riding[$event->getPlayer()->getName()])){
      unset($this->riding[$event->getPlayer()->getName()]);
    }
  }

  public function onPacketReceived(DataPacketReceiveEvent $event){
    $packet = $event->getPacket();
    $player = $event->getPlayer();
    if($packet instanceof InteractPacket){
      $boat = $player->getLevel()->getEntity($packet->target);
      if($boat instanceof Boat){
        if($packet->action === 1){
          $pk = new SetEntityLinkPacket();
          $pk->from = $boat->getId();
          $pk->to = $player->getId();
          $pk->type = 2;

          $this->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
          $pk = new SetEntityLinkPacket();
          $pk->from = $boat->getId();
          $pk->to = 0;
          $pk->type = 2;
          $player->dataPacket($pk);

          $this->riding[$player->getName()] = $packet->target;
        }elseif($packet->action === 3){
          $pk = new SetEntityLinkPacket();
          $pk->from = $boat->getId();
          $pk->to = $player->getId();
          $pk->type = 3;

          $this->getServer()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
          $pk = new SetEntityLinkPacket();
          $pk->from = $boat->getId();
          $pk->to = 0;
          $pk->type = 3;
          $player->dataPacket($pk);

          if(isset($this->riding[$event->getPlayer()->getName()])){
            unset($this->riding[$event->getPlayer()->getName()]);
          }
        }
      }
    }elseif($packet instanceof MovePlayerPacket){
      if(isset($this->riding[$player->getName()])){
        $boat = $player->getLevel()->getEntity($this->riding[$player->getName()]);
        if($boat instanceof Boat){
          $boat->x = $packet->x;
          $boat->y = $packet->y;
          $boat->z = $packet->z;
        }
      }
    }
  }
}
