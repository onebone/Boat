<?php

namespace onebone\boat\entity;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\EntityEventPacket;

class Boat extends Entity{
	const NETWORK_ID = 90;

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
}
