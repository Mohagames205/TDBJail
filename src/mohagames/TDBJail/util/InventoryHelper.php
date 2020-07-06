<?php


namespace mohagames\TDBJail\util;


use pocketmine\block\BlockFactory;
use pocketmine\block\BlockIds;
use pocketmine\inventory\ChestInventory;
use pocketmine\inventory\Inventory;
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
        if($from->getSize() <= $to->getSize())
        {
            $to->setContents($from->getContents());
            $from->setContents([]);
        }
    }

    public static function getOfflinePlayerInventory(string $playerName, Position $fakeInventoryPosition) : ?ChestInventory
    {
        if(!Helper::playerExists($playerName)) return null;
        $playerNBT = Server::getInstance()->getOfflinePlayerData($playerName);

        $inventoryNBT = $playerNBT->getListTag("Inventory")->getValue();
        $items = [];
        foreach ($inventoryNBT as $itemsNBT)
        {
            $item = null;
            foreach ($itemsNBT as $key => $itemNBT)
            {
                switch ($key)
                {
                    case "id":
                        $item = ItemFactory::get($itemNBT->getValue());
                        break;

                    case "Count":
                        $item->setCount($itemNBT->getValue());
                        break;

                    case "Damage":
                        $item->setDamage($itemNBT->getValue());
                        break;

                    case "tag":
                        var_dump($itemNBT->getValue());
                        $itemTag = $itemNBT->getValue();
                        if($itemNBT->getValue() == "display")
                        {
                            var_dump($itemTag);
                        }
                }
            }
            $items[] = $item;

        }

        $inv = self::createFakeInventory($fakeInventoryPosition);
        $inv->setContents($items);
        return $inv;
    }

    private static function createFakeInventory(Position $fakeInventoryPosition) : ChestInventory
    {
        $level = $fakeInventoryPosition->getLevel();
        $chest = BlockFactory::get(BlockIds::CHEST);
        $chest->position($fakeInventoryPosition);
        $fakeInventoryPosition->getLevel()->setBlock($fakeInventoryPosition, $chest);
        $level->addTile(new Chest($fakeInventoryPosition->getLevel(), Chest::createNBT($chest)));
        return new ChestInventory($fakeInventoryPosition->getLevel()->getTile($chest->asVector3()));
    }

    public static function destroyFakeInventory(ChestInventory $fakeInventory)
    {
        var_dump($fakeInventory->getHolder());
        $fakeInventory->getHolder()->getLevel()->setBlock($fakeInventory->getHolder(), BlockFactory::get(BlockIds::AIR));
    }


}