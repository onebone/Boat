<?php

namespace onebone\boat\item;

use onebone\boat\entity\Boat as BoatEntity;
use pocketmine\block\Block;
use pocketmine\item\{
	Boat as BoatItemPM, Item
};
use pocketmine\math\Vector3;
use pocketmine\nbt\tag\{
	Compound, Double, Enum, Float
};
use pocketmine\Player;

class Boat extends BoatItemPM{
	public function canBeActivated() : bool{
		return true;
	}

	public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector) : bool{
		$realPos = $blockClicked->getSide($face);

		$boat = new BoatEntity($player->getLevel()->getChunk($realPos->getX() >> 4, $realPos->getZ() >> 4), new Compound("", [
			"Pos" => new Enum("Pos", [
				new Double("", $realPos->getX()),
				new Double("", $realPos->getY()),
				new Double("", $realPos->getZ())
			]),
			"Motion" => new Enum("Motion", [
				new Double("", 0),
				new Double("", 0),
				new Double("", 0)
			]),
			"Rotation" => new Enum("Rotation", [
				new Float("", 0),
				new Float("", 0)
			]),
		]));
		$boat->spawnToAll();

		$item = $player->getInventory()->getItemInHand();
		if(--$item->count <= 0){
			$player->getInventory()->setItemInHand(Item::get(Item::AIR));
		}else{
			$player->getInventory()->setItemInHand($item);
		}
		return true;
	}
}
