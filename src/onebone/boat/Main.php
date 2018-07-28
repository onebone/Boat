<?php

namespace onebone\boat;

use onebone\boat\entity\Boat as BoatEntity;
use onebone\boat\item\Boat as BoatItem;
use onebone\boat\listener\EventListener;
use pocketmine\entity\Entity;
use pocketmine\inventory\ShapelessRecipe;
use pocketmine\item\{
	Item, ItemFactory
};
use pocketmine\plugin\PluginBase;

class Main extends PluginBase{
	public function onEnable() : void{
		ItemFactory::registerItem(new BoatItem(), true);
		Item::addCreativeItem(new BoatItem());
		$this->getServer()->getCraftingManager()->registerRecipe(
			new ShapelessRecipe(
				[
					Item::get(Item::WOODEN_PLANKS, 0, 5),
					Item::get(Item::WOODEN_SHOVEL, 0, 1)
				],
				[Item::get(333, 0, 1)])
		);

		Entity::registerEntity(BoatEntity::class, true);

		$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
	}
}
