<?php

namespace mohagames\TDBJail\jail;

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
        foreach (self::getJails() as $jail)
        {
            $bb = $jail->getBoundingBox();
            if($bb->isVectorInside($location) && $jail->getLevel()->getFolderName() == $jail->getLevel()->getFolderName()) return $jail;
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
    public static function createJail(string $name, AxisAlignedBB $axisAlignedBB, Level $level, Vector3 $spawn, array $members = [])
    {
        $levelName = $level->getName();
        $axisAlignedBB = serialize($axisAlignedBB);
        $spawn = Helper::vectorToArray($spawn);
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

         return new Jail($res["jail_name"], unserialize($res["jail_bb"]), Server::getInstance()->getLevelByName($res["jail_level"]), Helper::arrayToVector(json_decode($res["jail_spawn"], true)), json_decode($res["jail_members"], true));
    }

    public static function getJailByName(string $name) : ?Jail
    {
        $stmt = Main::getDb()->prepare("SELECT * FROM jails WHERE lower(jail_name) = lower(:name)");
        $stmt->bindParam("name", $name);
        $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if(!$res) return null;

        return new Jail($res["jail_name"], unserialize($res["jail_bb"]), Server::getInstance()->getLevelByName($res["jail_level"]), Helper::arrayToVector(json_decode($res["jail_spawn"], true)), json_decode($res["jail_members"], true));
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


}