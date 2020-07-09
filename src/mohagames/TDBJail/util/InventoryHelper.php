<?php


namespace mohagames\TDBJail\util;


use muqsit\invmenu\InvMenu;
use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\Inventory;
use pocketmine\item\Item;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\level\Position;
use pocketmine\math\Vector3;
use pocketmine\OfflinePlayer;
use pocketmine\Server;
use pocketmine\tile\Chest;

class InventoryHelper
{

    /** @var Position $fakeInventoryPosition */
    private $fakeInventoryPosition;

    public static function transferToInventory(Inventory $from, Inventory $to)
    {
        if(count($from->getContents()) <= $to->getSize())
        {
            $to->setContents($from->getContents());
            $from->setContents([]);
        }
    }

    public static function getOfflinePlayerInventory(string $playerName, Position $fakeInventoryPosition) : ?Inventory
    {
        if(!Helper::playerExists($playerName)) return null;
        $playerNBT = Server::getInstance()->getOfflinePlayerData($playerName);

        $inventoryNBT = $playerNBT->getListTag("Inventory")->getValue();
        $items = [];
        foreach ($inventoryNBT as $itemsNBT)
        {
            var_dump($itemsNBT);
            $items[] = Item::nbtDeserialize($itemsNBT);
        }

        $inv = InvMenu::create(InvMenu::TYPE_DOUBLE_CHEST);
        $inv->getInventory()->setContents($items);
        return $inv->getInventory();
    }


}