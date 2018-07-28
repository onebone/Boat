<?php

namespace onebone\boat\entity;

use onebone\boat\item\Boat as BoatItem;
use pocketmine\{
	math\Vector3, Player, Server
};
use pocketmine\block\Planks;
use pocketmine\entity\Entity;
use pocketmine\event\entity\{
	EntityDamageByEntityEvent, EntityDamageEvent, EntityRegainHealthEvent
};
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\{
	EntityEventPacket, SetEntityLinkPacket
};
use pocketmine\network\mcpe\protocol\types\EntityLink;

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

	/** @var Entity */
	public $rider;

	public function initEntity() : void{
		parent::initEntity();

		$woodId = $this->namedtag->getInt(self::TAG_WOOD_ID, Planks::OAK);
		if($woodId > 5 || $woodId < 0){
			$woodId = Planks::OAK;
		}
		$this->setWoodId($woodId);
		$this->setMaxHealth(4);
		$this->setGenericFlag(self::DATA_FLAG_STACKABLE, true);
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

	public function kill() : void{
		parent::kill();

		if($this->lastDamageCause instanceof EntityDamageByEntityEvent){
			$damager = $this->lastDamageCause->getDamager();
			if($damager instanceof Player and $damager->isCreative()){
				return;
			}
		}
		foreach($this->getDrops() as $item){
			$this->getLevel()->dropItem($this, $item);
		}
	}

	/**
	 * @param int $currentTick
	 *
	 * @return bool
	 */
	public function onUpdate(int $currentTick) : bool{
		if($this->closed){
			return false;
		}

		//Regenerate health 1⁄10 per tick
		if($this->getHealth() < $this->getMaxHealth() && $currentTick % 10 === 0){
			$this->heal(new EntityRegainHealthEvent($this, 1, EntityRegainHealthEvent::CAUSE_REGEN));
		}
		return parent::onUpdate($currentTick);
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

	/**
	 * @param Entity $rider
	 *
	 * @return bool
	 */
	public function canLink(Entity $rider) : bool{
		return $this->rider === null;
	}

	/**
	 * @param Entity $rider
	 *
	 * @return bool
	 */
	public function link(Entity $rider) : bool{
		if($this->rider === null){
			$rider->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_RIDING, true);

			$pk = new SetEntityLinkPacket();
			$pk->link = new EntityLink($this->getId(), $rider->getId(), EntityLink::TYPE_RIDER);
			Server::getInstance()->broadcastPacket($this->getViewers(), $pk);

			$this->rider = $rider;
			return true;
		}
		return false;
	}

	/**
	 * @param Entity $rider
	 *
	 * @return bool
	 */
	public function unlink(Entity $rider) : bool{
		if($this->rider === $rider){
			$rider->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_RIDING, false);

			$pk = new SetEntityLinkPacket();
			$pk->link = new EntityLink($this->getId(), $rider->getId(), EntityLink::TYPE_REMOVE);
			Server::getInstance()->broadcastPacket($this->getViewers(), $pk);

			$this->rider = null;
			return true;
		}
		return false;
	}

	/**
	 * @param Vector3    $pos
	 * @param float|null $yaw
	 * @param float|null $pitch
	 */
	public function absoluteMove(Vector3 $pos, ?float $yaw = null, ?float $pitch = null) : void{
		$this->setComponents($pos->x, $pos->y, $pos->z);
		$this->setRotation($yaw, $pitch);
		$this->updateMovement();
	}

	/**
	 * @return null|Entity
	 */
	public function getRider() : ?Entity{
		return $this->rider;
	}

	/**
	 * @param Entity $rider
	 *
	 * @return bool
	 */
	public function isRider(Entity $rider) : bool{
		return $this->rider === $rider;
	}
}
