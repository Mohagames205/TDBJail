<?php


namespace mohagames\TDBJail\util;


use mohagames\TDBJail\Main;
use pocketmine\item\Item;
use pocketmine\math\Vector3;

class Helper
{

    public static function isCorrectItem(Item $item)
    {
        $config  = Main::getInstance()->getConfig()->getAll();

        return $item->getId() == $config["item_id"] && in_array($config["lore"], $item->getLore());


    }

    public static function arrayToVector(array $arrayedVector)
    {
        return new Vector3($arrayedVector[0], $arrayedVector[1], $arrayedVector[2]);
    }

    public static function vectorToArray(Vector3 $vector3)
    {
        return [$vector3->getX(), $vector3->getY(), $vector3->getZ()];
    }


}