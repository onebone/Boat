<?php

namespace onebone\boat\item;

use onebone\boat\entity\Boat as BoatEntity;
use pocketmine\block\{
	Block, Planks
};
use pocketmine\item\Boat as BoatItemPM;
use pocketmine\math\Vector3;
use pocketmine\Player;

class Boat extends BoatItemPM{

	public function __construct(int $meta = 0){
		parent::__construct($meta);
		$this->name = $this->getVanillaName();
	}

	public function getVanillaName() : string{
		static $names = [
			Planks::OAK => "%item.boat.oak.name",
			Planks::SPRUCE => "%item.boat.spruce.name",
			Planks::BIRCH => "%item.boat.birch.name",
			Planks::JUNGLE => "%item.boat.jungle.name",
			Planks::ACACIA => "%item.boat.acacia.name",
			Planks::DARK_OAK => "%item.boat.dark_oak.name",
		];
		return $names[$this->meta] ?? "Boat";
	}

	public function onActivate(Player $player, Block $blockReplace, Block $blockClicked, int $face, Vector3 $clickVector) : bool{
		if ($player->isSurvival()) {
			$player->getInventory()->setItemInHand($this->pop());
		}

		$nbt = BoatEntity::createBaseNBT($blockClicked->getSide($face)->add(0.5, 0.5, 0.5));
		$nbt->setInt(BoatEntity::TAG_WOOD_ID, $this->meta);
		$boat = new BoatEntity($player->getLevel(), $nbt);
		$boat->spawnToAll();
		return true;
	}
}
