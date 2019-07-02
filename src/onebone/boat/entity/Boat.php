<?php

namespace onebone\boat\entity;

use onebone\boat\item\Boat as BoatItem;
use pocketmine\{
	math\Vector3, Player, Server
};
use pocketmine\block\Planks;
use pocketmine\entity\Entity;
use pocketmine\entity\Vehicle;
use pocketmine\event\entity\{
	EntityDamageByEntityEvent, EntityDamageEvent, EntityRegainHealthEvent
};
use pocketmine\item\Item;
use pocketmine\network\mcpe\protocol\{
	EntityEventPacket, SetEntityLinkPacket, AnimatePacket, AddEntityPacket
};
use pocketmine\network\mcpe\protocol\types\EntityLink;

class Boat extends Vehicle{
	public const NETWORK_ID = self::BOAT;

	public const TAG_WOOD_ID = "WoodID";

	public const ACTION_ROW_RIGHT = 128;
	public const ACTION_ROW_LEFT = 129;

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
			Server::getInstance()->broadcastPacket($this->getViewers(), $pk);
		}
	}

	/**
	 * Called by spawnTo() to send whatever packets needed to spawn the entity to the client.
	 *
	 * @param Player $player
	 * @override
	 */
	protected function sendSpawnPacket(Player $player) : void{
		$pk = new AddEntityPacket();
		$pk->entityRuntimeId = $this->getId();
		$pk->type = static::NETWORK_ID;
		$pk->position = $this->asVector3();
		$pk->motion = $this->getMotion();
		$pk->yaw = $this->yaw;
		$pk->headYaw = $this->yaw; //TODO
		$pk->pitch = $this->pitch;
		$pk->attributes = $this->attributeMap->getAll();
		$pk->metadata = $this->propertyManager->getAll();
		if($this->rider !== null){
			$pk->links[] = new EntityLink($this->getId(), $this->rider->getId(), EntityLink::TYPE_RIDER);
		}
		$player->dataPacket($pk);
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

			//Set the rider seat position to y + 1
			$rider->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, new Vector3(0, 1, 0));

			//Lock the rider rotation -90 to 90
			$rider->getDataPropertyManager()->setByte(self::DATA_RIDER_ROTATION_LOCKED, true);
			$rider->getDataPropertyManager()->setFloat(self::DATA_RIDER_MAX_ROTATION, 90);
			$rider->getDataPropertyManager()->setFloat(self::DATA_RIDER_MIN_ROTATION, -90);

			//Link entity to boat
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

			//Reset the rider seat position
			$rider->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, new Vector3(0, 0, 0));

			//Unlock the rider rotation
			$rider->getDataPropertyManager()->setByte(self::DATA_RIDER_ROTATION_LOCKED, false);

			//Unlink entity from boat
			$pk = new SetEntityLinkPacket();
			$pk->link = new EntityLink($this->getId(), $rider->getId(), EntityLink::TYPE_REMOVE);
			Server::getInstance()->broadcastPacket($this->getViewers(), $pk);

			$this->rider = null;
			return true;
		}
		return false;
	}

	/**
	 * @param Vector3 $pos
	 * @param float   $yaw   = 0
	 * @param float   $pitch = 0
	 */
	public function absoluteMove(Vector3 $pos, float $yaw = 0, float $pitch = 0) : void{
		$this->setComponents($pos->x, $pos->y, $pos->z);
		$this->setRotation($yaw, $pitch);
		$this->updateMovement();
	}

	/**
	 * @param AnimatePacket $packet
	 */
	public function handleAnimatePacket(AnimatePacket $packet) : void{
		if($this->rider !== null){
			switch($packet->action){
				case self::ACTION_ROW_RIGHT:
					$this->propertyManager->setFloat(self::DATA_PADDLE_TIME_RIGHT, $packet->float);
					break;

				case self::ACTION_ROW_LEFT:
					$this->propertyManager->setFloat(self::DATA_PADDLE_TIME_LEFT, $packet->float);
					break;
			}
		}
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
