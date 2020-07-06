<?php


namespace mohagames\TDBJail\util;


use pocketmine\inventory\Inventory;

class InventoryHelper
{

    public static function transferToInventory(Inventory $from, Inventory $to) : bool
    {
        if($from->getSize() <= $to->getSize())
        {
            $to->setContents($from->getContents());
            $from->setContents([]);
            return true;
        }
        return false;
    }


}
