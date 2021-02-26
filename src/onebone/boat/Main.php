<?php

namespace onebone\boat;

use onebone\boat\entity\Boat as BoatEntity;
use onebone\boat\item\Boat as BoatItem;
use onebone\boat\listener\EventListener;
use pocketmine\entity\Entity;
use pocketmine\inventory\ShapelessRecipe;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase{

    public function onLoad(): void{
        Entity::registerEntity(BoatEntity::class, true);
        ItemFactory::registerItem($item = new BoatItem(), true);
        if (!Item::isCreativeItem($item))
            Item::addCreativeItem($item);
    }

    public function onEnable() : void{
	$this->getServer()->getCraftingManager()->registerShapelessRecipe(new ShapelessRecipe([
            Item::get(Item::WOODEN_PLANKS, 0, 5),
            Item::get(Item::WOODEN_SHOVEL, 0, 1)
        ], [Item::get(Item::BOAT, 0, 1)]));
	$this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);
    }
}
