<?php

namespace onebone\boat\listener;

use onebone\boat\entity\Boat as BoatEntity;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerQuitEvent;
use pocketmine\event\server\DataPacketReceiveEvent;
use pocketmine\network\mcpe\protocol\{
	InteractPacket, MovePlayerPacket, SetEntityLinkPacket
};
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
		if($packet instanceof InteractPacket){
			$boat = $player->getLevel()->getEntity($packet->target);
			if($boat instanceof BoatEntity){
				if($packet->action === 1){
					$pk = new SetEntityLinkPacket();
					$pk->from = $boat->getId();
					$pk->to = $player->getId();
					$pk->type = 2;

					Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
					$pk = new SetEntityLinkPacket();
					$pk->from = $boat->getId();
					$pk->to = 0;
					$pk->type = 2;
					$player->dataPacket($pk);

					$this->riding[$player->getName()] = $packet->target;
				}elseif($packet->action === 3){
					$pk = new SetEntityLinkPacket();
					$pk->from = $boat->getId();
					$pk->to = $player->getId();
					$pk->type = 3;

					Server::getInstance()->broadcastPacket($player->getLevel()->getPlayers(), $pk);
					$pk = new SetEntityLinkPacket();
					$pk->from = $boat->getId();
					$pk->to = 0;
					$pk->type = 3;
					$player->dataPacket($pk);

					if(isset($this->riding[$event->getPlayer()->getName()])){
						unset($this->riding[$event->getPlayer()->getName()]);
					}
				}
			}
		}elseif($packet instanceof MovePlayerPacket){
			if(isset($this->riding[$player->getName()])){
				$boat = $player->getLevel()->getEntity($this->riding[$player->getName()]);
				if($boat instanceof BoatEntity){
					$boat->x = $packet->x;
					$boat->y = $packet->y;
					$boat->z = $packet->z;
				}
			}
		}
	}
}