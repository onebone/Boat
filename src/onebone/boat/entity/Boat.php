<?php

namespace onebone\boat\entity;

use pocketmine\entity\Entity;
use pocketmine\network\mcpe\protocol\AddEntityPacket;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\Player;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\network\mcpe\protocol\EntityEventPacket;
use pocketmine\item\Item;

class Boat extends Entity{
  const NETWORK_ID = 90;

  public function spawnTo(Player $player){
    $pk = new AddEntityPacket();
    $pk->eid = $this->getId();
    $pk->type = self::NETWORK_ID;
    $pk->x = $this->x;
    $pk->y = $this->y;
    $pk->z = $this->z;
    $pk->speedX = 0;
    $pk->speedY = 0;
    $pk->speedZ = 0;
    $pk->yaw = 0;
    $pk->pitch = 0;
    $pk->metadata = $this->dataProperties;
    $player->dataPacket($pk);

    parent::spawnTo($player);
  }

  public function attack($damage, EntityDamageEvent $source){
    parent::attack($damage, $source);

    if(!$source->isCancelled()){
      $pk = new EntityEventPacket();
  		$pk->eid = $this->id;
  		$pk->event = EntityEventPacket::HURT_ANIMATION;
      foreach($this->getLevel()->getPlayers() as $player){
        $player->dataPacket($pk);
      }
    }
  }

  public function kill(){
    parent::kill();

		foreach($this->getDrops() as $item){
			$this->getLevel()->dropItem($this, $item);
		}
  }

  public function getDrops(){
    return [
      Item::get(333, 0, 1)
    ];
  }

  public function getSaveId(){
    $class = new \ReflectionClass(static::class);
    return $class->getShortName();
  }
}
