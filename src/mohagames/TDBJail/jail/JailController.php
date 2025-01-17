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
use pocketmine\tile\Chest;
use SQLite3Stmt;

class JailController
{

    /**
     * @param Position $location
     * @return Jail|null
     */
    public static function getJailAtPosition(Position $location): ?Jail
    {
        $newPosition = new Vector3($location->getFloorX(), $location->getFloorY(), $location->getFloorZ());

        foreach (self::getJails() as $jail) {
            $bb = $jail->getBoundingBox();
            if ($bb->isVectorInside($newPosition) && ($location->getLevel()->getFolderName() == $jail->getLevel()->getFolderName())) return $jail;
        }
        return null;
    }

    /**
     * @param string $name
     * @param AxisAlignedBB $axisAlignedBB
     * @param Level $level
     * @param Vector3 $spawn
     * @param string|null $member
     */
    public static function createJail(string $name, AxisAlignedBB $axisAlignedBB, Level $level, ?Vector3 $spawn = null, ?string $member = null) : void
    {
        if (!is_null(JailController::getJailByName($name))) return;

        $levelName = $level->getFolderName();
        $axisAlignedBB = serialize($axisAlignedBB->expand(1, 0, 1));
        $spawn = !is_null($spawn) ? Helper::vectorToArray($spawn) : null;

        /** @var SQLite3Stmt $stmt */
        $stmt = Main::getDb()->prepare("INSERT INTO jails (jail_name, jail_bb, jail_level, jail_spawn, jail_member) values(:name, :bb, :level, :spawn, :member)");
        $stmt->bindParam("name", $name);
        $stmt->bindParam("bb", $axisAlignedBB);
        $stmt->bindParam("level", $levelName);
        $stmt->bindParam("spawn", $spawn);
        $stmt->bindParam("member", $member);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param int $id
     * @return Jail|null
     */
    public static function getJailById(int $id): ?Jail
    {

        /** @var SQLite3Stmt $stmt */
        $stmt = Main::getDb()->prepare("SELECT * FROM jails WHERE jail_id = :id");
        $stmt->bindParam("id", $id);
        $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if (!$res) return null;

        $level = Server::getInstance()->getLevelByName($res["jail_level"]);
        $spawn = !is_null(json_decode($res["jail_spawn"], true)) ? Helper::arrayToVector(json_decode($res["jail_spawn"], true)) : null;

        /** @var Chest|null $chest */
        $chest = !is_null($res["jail_chest"]) ? $level->getTile(Helper::arrayToVector(json_decode($res["jail_chest"], true))) : null;

        return new Jail($res["jail_name"], unserialize($res["jail_bb"]), $level, $spawn, $res["jail_member"], $chest);
    }

    public static function getJailByName(string $name): ?Jail
    {

        /** @var SQLite3Stmt $stmt */
        $stmt = Main::getDb()->prepare("SELECT * FROM jails WHERE lower(jail_name) = lower(:name)");
        $stmt->bindParam("name", $name);

        $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        if (!$res) return null;
        $level = Server::getInstance()->getLevelByName($res["jail_level"]);
        $spawn = !is_null(json_decode($res["jail_spawn"], true)) ? Helper::arrayToVector(json_decode($res["jail_spawn"], true)) : null;

        /** @var Chest|null $chest */
        $chest = !is_null($res["jail_chest"]) ? $level->getTile(Helper::arrayToVector(json_decode($res["jail_chest"], true))) : null;

        return new Jail($res["jail_name"], unserialize($res["jail_bb"]), $level, $spawn, $res["jail_member"], $chest);
    }


    /**
     * @return Jail[]|array
     */
    public static function getJails(): array
    {

        /** @var SQLite3Stmt $stmt */
        $stmt = Main::getDb()->prepare("SELECT * FROM jails");
        $res = $stmt->execute();

        while ($row = $res->fetchArray()) {
            $jails[] = self::getJailById($row["jail_id"]);
        }

        return $jails ?? [];
    }

    /**
     * @param string $playerName
     * @return Jail|null
     */
    public static function getJailByMember(string $playerName): ?Jail
    {

        /** @var SQLite3Stmt $stmt */
        $stmt = Main::getDb()->prepare("SELECT jail_id FROM jails WHERE lower(jail_member) = lower(:playername)");
        $stmt->bindParam("playername", $playerName);

        $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        $stmt->close();

        if (!$res) return null;

        return self::getJailById($res["jail_id"]);
    }


}
