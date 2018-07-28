<?php

namespace onebone\boat\item;

use onebone\boat\entity\Boat as BoatEntity;
use pocketmine\block\Block;
use pocketmine\item\{
	Boat as BoatItemPM, Item
};
use pocketmine\math\Vector3;
use pocketmine\Player;

class Boat extends BoatItemPM{
	public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector) : bool{
		$boat = new BoatEntity($player->getLevel(), BoatEntity::createBaseNBT($blockClicked->getSide($face)));
		$boat->spawnToAll();

		if(!$player->isCreative()){
			$item = $player->getInventory()->getItemInHand();
			if(--$item->count <= 0){
				$player->getInventory()->setItemInHand(Item::get(Item::AIR));
			}else{
				$player->getInventory()->setItemInHand($item);
			}
		}
		return true;
	}
}
