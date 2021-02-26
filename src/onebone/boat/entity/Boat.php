<?php

namespace onebone\boat\entity;

use onebone\boat\item\Boat as BoatItem;
use pocketmine\network\mcpe\protocol\AddActorPacket;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\math\Vector3;
use pocketmine\block\Planks;
use pocketmine\entity\Entity;
use pocketmine\entity\Vehicle;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\network\mcpe\protocol\ActorEventPacket;
use pocketmine\network\mcpe\protocol\SetActorLinkPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\types\EntityLink;

class Boat extends Vehicle{

	public const NETWORK_ID = self::BOAT;

	public const TAG_WOOD_ID = "WoodID";

	public const ACTION_ROW_RIGHT = 128;
	public const ACTION_ROW_LEFT = 129;

	public $height = 0.455;

	public $width = 1.4;

	/** @var float */
	public $gravity = 0.0;
	/** @var float */
	public $drag = 0.1;

	public ?Entity $rider = null;


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

	public function attack(EntityDamageEvent $source) : void{
		parent::attack($source);

		if(!$source->isCancelled()){
			$pk = new ActorEventPacket();
			$pk->entityRuntimeId = $this->id;
			$pk->event = ActorEventPacket::HURT_ANIMATION;
			Server::getInstance()->broadcastPacket($this->getViewers(), $pk);
		}
	}

	protected function sendSpawnPacket(Player $player) : void{
	    $pk = new AddActorPacket();
	    $pk->type = "minecraft:boat";
	    $pk->entityRuntimeId = $this->getId();
	    $pk->position = $this->getPosition();
	    $pk->motion = $this->getMotion();
	    $pk->attributes = $this->getAttributeMap()->getAll();
	    $pk->metadata = $this->getDataPropertyManager()->getAll();
	    if ($this->rider !== null) {
            $pk->links[] = new EntityLink($this->getId(), $this->rider->getId(), EntityLink::TYPE_RIDER, true, true);
        }

	    $player->sendDataPacket($pk);
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

	public function onUpdate(int $currentTick) : bool{
		if($this->closed){
			return false;
		}

		if($this->getHealth() < $this->getMaxHealth() && $currentTick % 10 === 0){
			$this->heal(new EntityRegainHealthEvent($this, 1, EntityRegainHealthEvent::CAUSE_REGEN));
		}

		return parent::onUpdate($currentTick);
	}

	public function getDrops() : array{
		return [
			new BoatItem($this->getWoodId())
		];
	}

	public function getWoodId() : int{
		return $this->propertyManager->getInt(self::DATA_VARIANT);
	}

	public function setWoodId(int $woodId) : void{
		$this->propertyManager->setInt(self::DATA_VARIANT, $woodId);
	}

	public function canLink(Entity $rider) : bool{
		return $this->rider === null;
	}

	public function link(Entity $rider) : bool{
		if($this->rider === null){
			$rider->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_RIDING, true);
			$rider->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, new Vector3(0, 1, 0));
			$rider->getDataPropertyManager()->setByte(self::DATA_RIDER_ROTATION_LOCKED, true);
			$rider->getDataPropertyManager()->setFloat(self::DATA_RIDER_MAX_ROTATION, 90);
			$rider->getDataPropertyManager()->setFloat(self::DATA_RIDER_MIN_ROTATION, -90);

			$pk = new SetActorLinkPacket();
			$pk->link = new EntityLink($this->getId(), $rider->getId(), EntityLink::TYPE_RIDER, true, true);
			Server::getInstance()->broadcastPacket($this->getViewers(), $pk);

			$this->rider = $rider;
			return true;
		}

		return false;
	}

	public function unlink(Entity $rider) : bool{
		if($this->rider === $rider){
			$rider->setDataFlag(self::DATA_FLAGS, self::DATA_FLAG_RIDING, false);
			$rider->getDataPropertyManager()->setVector3(Entity::DATA_RIDER_SEAT_POSITION, new Vector3(0, 0, 0));
			$rider->getDataPropertyManager()->setByte(self::DATA_RIDER_ROTATION_LOCKED, false);

			$pk = new SetActorLinkPacket();
			$pk->link = new EntityLink($this->getId(), $rider->getId(), EntityLink::TYPE_REMOVE, true, true);
			Server::getInstance()->broadcastPacket($this->getViewers(), $pk);

			$this->rider = null;
			return true;
		}

		return false;
	}

	public function absoluteMove(Vector3 $pos, float $yaw = 0, float $pitch = 0) : void{
		$this->setComponents($pos->x, $pos->y, $pos->z);
		$this->setRotation($yaw, $pitch);
		$this->updateMovement();
	}

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

	public function getRider() : ?Entity{
		return $this->rider;
	}

	public function isRider(Entity $rider) : bool{
		return $this->rider === $rider;
	}
}
