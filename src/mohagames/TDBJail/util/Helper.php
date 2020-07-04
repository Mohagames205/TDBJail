<?php


namespace mohagames\TDBJail\util;


use mohagames\TDBJail\Main;
use pocketmine\item\Item;
use pocketmine\math\Vector3;

class Helper
{

    public static function isCorrectItem(Item $item) : bool
    {
        $config = Main::getInstance()->getConfig()->getAll();

        return $item->getId() == $config["item_id"] && in_array($config["lore"], $item->getLore());


    }

    public static function arrayToVector(array $arrayedVector) : Vector3
    {
        return new Vector3($arrayedVector[0], $arrayedVector[1], $arrayedVector[2]);
    }

    public static function vectorToArray(Vector3 $vector3) : array
    {
        return [$vector3->getX(), $vector3->getY(), $vector3->getZ()];
    }

    /**
     * Deze method checkts als de gegeven speler ooit de server heeft gejoined.
     *
     * @param string $playername
     * @return bool
     */
    public static function playerExists(?string $playername) : bool
    {
        if (!is_null($playername)) {
            $playername = strtolower($playername);
            return file_exists("players/$playername.dat");
        }
        return false;

    }


}