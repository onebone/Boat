<?php

namespace onebone\boat\listener;

use onebone\boat\entity\Boat as BoatEntity;
use pocketmine\entity\Entity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\{
	InteractPacket, InventoryTransactionPacket, MoveEntityAbsolutePacket, PlayerInputPacket, SetEntityMotionPacket
};

class EventListener implements Listener{
	/**
	 * @param PlayerQuitEvent $event
	 */
	public function onPlayerQuitEvent(PlayerQuitEvent $event) : void{
		$player = $event->getPlayer();
		if($player->getDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_RIDING)){
			foreach($player->getLevel()->getNearbyEntities($player->getBoundingBox()->expand(2, 2, 2), $player) as $key => $entity){
				if($entity instanceof BoatEntity && $entity->unlink($player)){
					return;
				}
			}
		}
	}

	/**
	 * @param DataPacketReceiveEvent $event
	 */
	public function onDataPacketReceiveEvent(DataPacketReceiveEvent $event) : void{
		$packet = $event->getPacket();
		$player = $event->getPlayer();
		if($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY){
			$boat = $player->getLevel()->getEntity($packet->trData->entityRuntimeId);
			if($boat instanceof BoatEntity){
				if($packet->trData->actionType === InventoryTransactionPacket::USE_ITEM_ON_ENTITY_ACTION_INTERACT && $boat->canLink($player)){
					$boat->link($player);
				}
				$event->setCancelled();
			}
		}elseif($packet instanceof InteractPacket){
			$boat = $player->getLevel()->getEntity($packet->target);
			if($boat instanceof BoatEntity){
				if($packet->action === InteractPacket::ACTION_LEAVE_VEHICLE && $boat->isRider($player)){
					$boat->unlink($player);
				}
				$event->setCancelled();
			}
		}elseif($packet instanceof MoveEntityAbsolutePacket){
			$boat = $player->getLevel()->getEntity($packet->entityRuntimeId);
			if($boat instanceof BoatEntity && $boat->isRider($player)){
				$boat->absoluteMove($packet->position, $packet->xRot, $packet->zRot);
				$event->setCancelled();
			}
		}elseif($packet instanceof PlayerInputPacket || $packet instanceof SetEntityMotionPacket){
			if($player->getDataFlag(Entity::DATA_FLAGS, Entity::DATA_FLAG_RIDING)){
				//TODO: Handle PlayerInputPacket and SetEntityMotionPacket
				$event->setCancelled();
			}
		}
	}
}