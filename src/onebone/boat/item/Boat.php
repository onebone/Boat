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
	/**
	 * Called when a player uses this item on a block.
	 *
	 * @param Player  $player
	 * @param Block   $blockReplace
	 * @param Block   $blockClicked
	 * @param int     $face
	 * @param Vector3 $clickVector
	 *
	 * @return bool
	 */
	public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector) : bool{
		//Spawn boat entity
		$boat = new BoatEntity($player->getLevel(), BoatEntity::createBaseNBT($blockClicked->getSide($face)->add(0.5, 0.5, 0.5)));
		$boat->spawnToAll();

		//Reduce boat item count
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
