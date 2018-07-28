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
	/**
	 * Called when the plugin is enabled
	 */
	public function onEnable() : void{
		//Register boat items
		ItemFactory::registerItem(new BoatItem(), true);
		$this->getServer()->getCraftingManager()->registerRecipe(
			new ShapelessRecipe(
				[
					Item::get(Item::WOODEN_PLANKS, 0, 5),
					Item::get(Item::WOODEN_SHOVEL, 0, 1)
				],
				[Item::get(Item::BOAT, 0, 1)])
		);

		//Register boat entities
		Entity::registerEntity(BoatEntity::class, true);

		//Register event listeners
		$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
	}
}
