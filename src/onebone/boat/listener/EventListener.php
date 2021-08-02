<?php

namespace onebone\boat\listener;

use onebone\boat\entity\Boat as BoatEntity;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\InteractPacket;
use pocketmine\network\mcpe\protocol\InventoryTransactionPacket;
use pocketmine\network\mcpe\protocol\MoveActorAbsolutePacket;
use pocketmine\network\mcpe\protocol\PlayerInputPacket;
use pocketmine\network\mcpe\protocol\SetActorMotionPacket;
use pocketmine\network\mcpe\protocol\AnimatePacket;
use pocketmine\network\mcpe\protocol\types\inventory\UseItemOnEntityTransactionData;

class EventListener implements Listener{
	
	/** @priority HIGHEST */
	public function onPlayerQuitEvent(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		if (!$player->getDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_RIDING))
		    return;

		foreach($player->getLevel()->getNearbyEntities($player->getBoundingBox()->expand(2, 2, 2), $player) as $key => $entity) {
			if ($entity instanceof BoatEntity && $entity->unlink($player))
				return;
		}
	}
	
	/** @priority HIGHEST */
	public function onDataPacketReceiveEvent(DataPacketReceiveEvent $event) : void{
		$packet = $event->getPacket();
		$player = $event->getPlayer();
		if ($packet instanceof InventoryTransactionPacket) {
      if ($packet->trData->getTypeId() !== InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY)
                return;

      $entity = $player->getLevel()->getEntity($packet->trData->getEntityRuntimeId());
      if (!$entity instanceof BoatEntity)
                return;

      if ($packet->trData->getActionType() !== UseItemOnEntityTransactionData::ACTION_INTERACT)
        return;
			
			if($entity->canLink($player)){
				$entity->link($player);
			}
			
			$event->setCancelled();
		} else if ($packet instanceof InteractPacket) {
			$entity = $player->getLevel()->getEntity($packet->target);
			if (!$entity instanceof BoatEntity)
				return;
			
			if ($packet->action === InteractPacket::ACTION_LEAVE_VEHICLE && $entity->isRider($player)){
				$entity->unlink($player);
			}

			$event->setCancelled();
		} else if ($packet instanceof MoveActorAbsolutePacket) {
			$entity = $player->getLevel()->getEntity($packet->entityRuntimeId);
			if ($entity instanceof BoatEntity && $entity->isRider($player)) {
				$entity->absoluteMove($packet->position, $packet->xRot, $packet->zRot);
				$event->setCancelled();
			}
		} else if ($packet instanceof AnimatePacket) {
			foreach ($player->getLevel()->getEntities() as $entity) {
				if ($entity instanceof BoatEntity && $entity->isRider($player)){
					switch ($packet->action) {
						case BoatEntity::ACTION_ROW_RIGHT:
						case BoatEntity::ACTION_ROW_LEFT:
							$entity->handleAnimatePacket($packet);
							$event->setCancelled();
							break;
					}
					break;
				}
			}
		} else if ($packet instanceof PlayerInputPacket or $packet instanceof SetActorMotionPacket) {
			if (!$player->getDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_RIDING))
				return;
			
			$event->setCancelled();
		}
	}
}
