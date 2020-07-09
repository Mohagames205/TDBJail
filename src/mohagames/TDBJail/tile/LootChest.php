<?php


namespace mohagames\TDBJail\tile;


use pocketmine\item\Item;
use pocketmine\item\ItemIds;
use pocketmine\level\Level;
use pocketmine\nbt\tag\CompoundTag;
use pocketmine\nbt\tag\IntTag;
use pocketmine\nbt\tag\NamedTag;
use pocketmine\tile\Chest;

class LootChest extends Chest
{

    public $name = "LootChest";

    public function __construct(Level $level, CompoundTag $nbt)
    {
        parent::__construct($level, $nbt);
        $this->scheduleUpdate();
    }

    public function onUpdate(): bool
    {
        if(count($this->getInventory()->getContents()) == 0)
        {
            $this->initLayout();
            return true;
        }
        return parent::onUpdate();
    }

    private function initLayout() : void
    {
        $invisBedrock = Item::get(ItemIds::INVISIBLE_BEDROCK)->setCustomName("§cBARRIER");
        $invisBedrock->setNamedTagEntry(new IntTag("blockmove", 1));

        $contents = [];
        foreach ($this->getInventory()->getContents(true) as $slot => $item)
        {
            if($slot <= 26) $contents[$slot] = Item::get(ItemIds::AIR);
            if($slot >= (9 * 3) && $slot <= (9 * 4) || $slot >= 44 && $slot <= 53) $contents[$slot] = $invisBedrock;
            if($slot == 38 || $slot == 40 || $slot == 42) $contents[$slot] = $invisBedrock;
        }
        $this->getInventory()->setContents($contents);
    }




}