<?php

namespace onebone\boat\entity;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\EntityEventPacket;

class Boat extends Entity{
	const NETWORK_ID = 90;

	public $height = 0.455;
	public $width = 1.4;

	public $gravity = 0;
	public $drag = 0.1;

	public function initEntity() : void{
		$this->setMaxHealth(4);
		//TODO: Set Entity::DATA_RIDER_SEAT_POSITION

		parent::initEntity();
	}

	public function attack(EntityDamageEvent $source) : void{
		parent::attack($source);

		if(!$source->isCancelled()){
			$pk = new EntityEventPacket();
			$pk->entityRuntimeId = $this->id;
			$pk->event = EntityEventPacket::HURT_ANIMATION;
			foreach($this->getLevel()->getPlayers() as $player){
				$player->dataPacket($pk);
			}
		}
	}

	public function getDrops() : array{
		return [
			Item::get(Item::BOAT, 0, 1)
		];
	}
}
