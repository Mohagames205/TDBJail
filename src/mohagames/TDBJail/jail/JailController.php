<?php

namespace mohagames\TDBJail\jail;

use mohagames\TDBJail\event\EventListener;
use mohagames\TDBJail\Main;
use mohagames\TDBJail\util\Helper;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Server;

class JailController {

    /**
     * @param Position $location
     * @return Jail|null
     */
    public static function getJailAtPosition(Position $location) : ?Jail
    {
        $newPosition = new Vector3($location->getFloorX(), $location->getFloorY(), $location->getFloorZ());

        foreach (self::getJails() as $jail)
        {
            $bb = $jail->getBoundingBox();
            if($bb->isVectorInside($newPosition) && $jail->getLevel()->getFolderName() == $jail->getLevel()->getFolderName()) return $jail;
        }
        return null;
    }

    /**
     * @param string $name
     * @param AxisAlignedBB $axisAlignedBB
     * @param Level $level
     * @param Vector3 $spawn
     * @param array $members
     */
    public static function createJail(string $name, AxisAlignedBB $axisAlignedBB, Level $level, ?Vector3 $spawn = null, array $members = [])
    {
        $levelName = $level->getName();
        $axisAlignedBB = serialize($axisAlignedBB->expand(1, 0, 1));
        $spawn = !is_null($spawn) ? Helper::vectorToArray($spawn) : null;
        $members = json_encode($members);

        $stmt = Main::getDb()->prepare("INSERT INTO jails (jail_name, jail_bb, jail_level, jail_spawn, jail_members) values(:name, :bb, :level, :spawn, :members)");
        $stmt->bindParam("name", $name);
        $stmt->bindParam("bb", $axisAlignedBB);
        $stmt->bindParam("level", $levelName);
        $stmt->bindParam("spawn", $spawn);
        $stmt->bindParam("members", $members);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param int $id
     * @return Jail|null
     */
    public static function getJailById(int $id) : ?Jail
    {
        $stmt = Main::getDb()->prepare("SELECT * FROM jails WHERE jail_id = :id");
        $stmt->bindParam("id", $id);
        $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if(!$res) return null;
        $spawn = !is_null(json_decode($res["jail_spawn"], true)) ? Helper::arrayToVector(json_decode($res["jail_spawn"], true)) : null;

         return new Jail($res["jail_name"], unserialize($res["jail_bb"]), Server::getInstance()->getLevelByName($res["jail_level"]), $spawn, json_decode($res["jail_members"], true));
    }

    public static function getJailByName(string $name) : ?Jail
    {
        $stmt = Main::getDb()->prepare("SELECT * FROM jails WHERE lower(jail_name) = lower(:name)");
        $stmt->bindParam("name", $name);
        $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if(!$res) return null;
        $spawn = !is_null(json_decode($res["jail_spawn"], true)) ? Helper::arrayToVector(json_decode($res["jail_spawn"], true)) : null;
        return new Jail($res["jail_name"], unserialize($res["jail_bb"]), Server::getInstance()->getLevelByName($res["jail_level"]), $spawn, json_decode($res["jail_members"], true));
    }


    /**
     * @return Jail[]|null
     */
    public static function getJails() : ?array
    {
        $stmt = Main::getDb()->prepare("SELECT * FROM jails");
        $res = $stmt->execute();

        while($row = $res->fetchArray())
        {
            $jails[] = self::getJailById($row["jail_id"]);
        }

        return $jails ?? null;
    }

    /**
     * @param string $playerName
     * @return Jail|null
     *
     * TODO: Een manier vinden om dit efficiënter te maken!
     *
     * @see EventListener::onMove()
     */
    public static function isJailed(string $playerName) : ?Jail
    {
        $jails = JailController::getJails();

        foreach ($jails as $jail)
        {
            if($jail->isJailed($playerName)) return $jail;
        }
        return null;
    }



}