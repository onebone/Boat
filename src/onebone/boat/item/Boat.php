<?php

namespace onebone\boat\item;

use pocketmine\item\Item;
use pocketmine\level\Level;
use pocketmine\block\Block;
use pocketmine\Player;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\EnumTag;
use pocketmine\nbt\tag\DoubleTag;
use pocketmine\nbt\tag\FloatTag;

use onebone\boat\entity\Boat as BoatEntity;

class Boat extends Item{
  public function __construct($meta = 0, $count = 1){
		parent::__construct(333, $meta, $count, "Boat");
	}

  public function canBeActivated(){
    return true;
  }

  public function onActivate(Level $level, Player $player, Block $block, Block $target, $face, $fx, $fy, $fz){
    $realPos = $block->getSide($face);

    $boat = new BoatEntity($player->getLevel()->getChunk($realPos->getX() >> 4, $realPos->getZ() >> 4), new CompoundTag("", [
  			"Pos" => new EnumTag("Pos", [
  				new DoubleTag("", $realPos->getX()),
  				new DoubleTag("", $realPos->getY()),
  				new DoubleTag("", $realPos->getZ())
  			]),
  			"Motion" => new Enum("Motion", [
  				new DoubleTag("", 0),
  				new DoubleTag("", 0),
  				new DoubleTag("", 0)
  			]),
  			"Rotation" => new EnumTag("Rotation", [
  				new FloatTag("", 0),
  				new FloatTag("", 0)
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
