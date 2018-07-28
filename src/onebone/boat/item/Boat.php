<?php

namespace onebone\boat\item;

use onebone\boat\entity\Boat as BoatEntity;
use pocketmine\block\Block;
use pocketmine\item\{
	Boat as BoatItemPM, Item
};
use pocketmine\level\Level;
use pocketmine\nbt\tag\{
	Compound, Double, Enum, Float
};
use pocketmine\Player;

class Boat extends BoatItemPM{
	public function canBeActivated() : bool{
		return true;
	}

	public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz) : bool{
		$realPos = $block->getSide($face);

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
		$count = $item->getCount();
		if(--$count <= 0){
			$player->getInventory()->setItemInHand(Item::get(Item::AIR));
			return;
		}

		$item->setCount($count);
		$player->getInventory()->setItemInHand($item);
		return true;
	}
}
