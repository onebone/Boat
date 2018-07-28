<?php

namespace onebone\boat\entity;

use onebone\boat\item\Boat as BoatItem;
use pocketmine\block\Planks;
use pocketmine\entity\Entity;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\EntityEventPacket;

class Boat extends Entity{
	public const NETWORK_ID = self::BOAT;

	public const TAG_WOOD_ID = "WoodID";

	/** @var float */
	public $height = 0.455;
	/** @var float */
	public $width = 1.4;

	/** @var float */
	public $gravity = 0.0;
	/** @var float */
	public $drag = 0.1;

	public function initEntity() : void{
		parent::initEntity();

		$woodId = $this->namedtag->getInt(self::TAG_WOOD_ID, Planks::OAK);
		if($woodId > 5 || $woodId < 0){
			$woodId = Planks::OAK;
		}
		$this->setWoodId($woodId);
		$this->setMaxHealth(4);
	}

	public function saveNBT() : void{
		parent::saveNBT();
		$this->namedtag->setInt(self::TAG_WOOD_ID, $this->getWoodId());
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
			new BoatItem($this->getWoodId())
		];
	}

	/**
	 * @return int
	 */
	public function getWoodId() : int{
		return $this->propertyManager->getInt(self::DATA_VARIANT);
	}

	/**
	 * @param int $woodId
	 */
	public function setWoodId(int $woodId) : void{
		$this->propertyManager->setInt(self::DATA_VARIANT, $woodId);
	}
}
