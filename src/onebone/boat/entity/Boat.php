<?php

namespace onebone\boat\entity;

use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\EntityEventPacket;

class Boat extends Entity{
	public const NETWORK_ID = self::BOAT;

	/** @var float */
	public $height = 0.455;
	/** @var float */
	public $width = 1.4;

	/** @var float */
	public $gravity = 0.0;
	/** @var float */
	public $drag = 0.1;

	public function initEntity() : void{
		$this->setMaxHealth(4);

		parent::initEntity();
	}

	/**
	 * @param EntityDamageEvent $source
	 */
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

	/**
	 * @return Item[]
	 */
	public function getDrops() : array{
		return [
			Item::get(Item::BOAT, 0, 1)
		];
	}
}
