<?php

namespace onebone\boat\listener;

use onebone\boat\entity\Boat as BoatEntity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\{
	InteractPacket, InventoryTransactionPacket, MoveEntityAbsolutePacket, SetEntityLinkPacket
};
use pocketmine\network\mcpe\protocol\types\EntityLink;
use pocketmine\Server;

class EventListener implements Listener{
	private $riding = [];

	public function onQuit(PlayerQuitEvent $event) : void{
		if(isset($this->riding[$event->getPlayer()->getName()])){
			unset($this->riding[$event->getPlayer()->getName()]);
		}
	}

	public function onPacketReceived(DataPacketReceiveEvent $event) : void{
		$packet = $event->getPacket();
		$player = $event->getPlayer();
		if($packet instanceof InventoryTransactionPacket && $packet->transactionType === InventoryTransactionPacket::TYPE_USE_ITEM_ON_ENTITY){
			$boat = $player->getLevel()->getEntity($packet->trData->entityRuntimeId);
			if($boat instanceof BoatEntity){
				$pk = new SetEntityLinkPacket();
				$pk->link = new EntityLink($player->getId(), $boat->getId(), EntityLink::TYPE_RIDER);
				Server::getInstance()->broadcastPacket($player->getViewers(), $pk);
				$player->dataPacket($pk);

				$this->riding[$player->getName()] = $packet->trData->entityRuntimeId;
			}
		}elseif($packet instanceof InteractPacket && $packet->action === InteractPacket::ACTION_LEAVE_VEHICLE){
			$boat = $player->getLevel()->getEntity($packet->target);
			if($boat instanceof BoatEntity){
				$pk = new SetEntityLinkPacket();
				$pk->link = new EntityLink($player->getId(), $boat->getId(), EntityLink::TYPE_REMOVE);
				Server::getInstance()->broadcastPacket($player->getViewers(), $pk);
				$player->dataPacket($pk);

				if(isset($this->riding[$event->getPlayer()->getName()])){
					unset($this->riding[$event->getPlayer()->getName()]);
				}
			}
		}elseif($packet instanceof MoveEntityAbsolutePacket){
			if(isset($this->riding[$player->getName()])){
				$boat = $player->getLevel()->getEntity($this->riding[$player->getName()]);
				if($boat instanceof BoatEntity){
					$boat->teleport($packet->position, $packet->xRot, $packet->zRot);
				}
			}
		}
	}
}