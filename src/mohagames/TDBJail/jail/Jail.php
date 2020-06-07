<?php

namespace mohagames\TDBJail\jail;

use mohagames\PlotArea\utils\Member;
use mohagames\TDBJail\Main;
use mohagames\TDBJail\util\Helper;
use pocketmine\level\Level;
use pocketmine\level\Location;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;

class Jail {

    /**
     * @var AxisAlignedBB
     */
    private $boundingBox;

    /**
     * @var string
     */
    private $name;

    /**
     * @var Level
     */
    private $level;

    /**
     * @var Vector3
     */
    private $spawn;

    /**
     * @var string
     */
    private $member;

    public function __construct(string $name, AxisAlignedBB $boundingBox, Level $level, ?Vector3 $spawn = null, string $member = null)
    {
        $this->name = $name;
        $this->boundingBox = $boundingBox;
        $this->level = $level;
        $this->spawn = $spawn;
        $this->member = $member;
    }

    public function getBoundingBox() : AxisAlignedBB
    {
        return $this->boundingBox;
    }

    public function getSpawn() : Vector3
    {
        $bb = $this->boundingBox;

        $defaultSpawn = Helper::arrayToVector([($bb->maxX + $bb->minX)/2,$bb->minY + 1, ($bb->maxZ + $bb->minZ)/2]);

        return $this->spawn ?? $defaultSpawn;
    }

    public function getName() : string
    {
        return $this->name;
    }

    public function setSpawn(Vector3 $spawn) : void
    {
        $id = $this->getId();
        $encodedSpawn = json_encode(Helper::vectorToArray($spawn));
        //db query
        $stmt = Main::getDb()->prepare("UPDATE jails SET jail_spawn = :spawn WHERE jail_id = :jail_id");
        $stmt->bindParam("spawn", $encodedSpawn);
        $stmt->bindParam("jail_id", $id);
        $stmt->execute();
        $stmt->close();

        $this->spawn = $spawn;
    }

    public function getLevel() : Level
    {
        return $this->level;
    }

    public function delete() : void
    {
        $id = $this->getId();

        $stmt = Main::getDb()->prepare("DELETE FROM jails WHERE jail_id = :id");
        $stmt->bindParam("id", $id);
        $stmt->execute();
        $stmt->close();
    }

    public function setTime(int $time)
    {
        $id = $this->getId();

        $stmt = Main::getDb()->prepare("UPDATE jails SET jail_time = :time WHERE jail_id = :jail_id");
        $stmt->bindParam("time", $time);
        $stmt->bindParam("jail_id", $id);
        $stmt->execute();
        $stmt->close();
    }

    public function setMember(string $member, int $time) : bool
    {
        if(!$this->isJailed($member))
        {
            if(Helper::playerExists($member))
            {
                $this->member = strtolower($member);

                $id = $this->getId();
                $stmt = Main::getDb()->prepare("UPDATE jails SET jail_member = lower(:member) WHERE jail_id = :id");
                $stmt->bindParam("member", $member);
                $stmt->bindParam("id", $id);
                $stmt->execute();

                $this->setTime($time);
                return true;
            }
        }
        return false;
    }

    public function deleteMember()
    {
        $id = $this->getId();
        $this->member = null;
        $stmt = Main::getDb()->prepare("UPDATE jails SET jail_member = NULL WHERE jail_id = :jail_id");
        $stmt->bindParam("jail_id", $id);
        $stmt->execute();
        $stmt->close();

        return true;
    }

    public function getRemainingTime()
    {
        $id = $this->getId();

        $stmt = Main::getDb()->prepare("SELECT jail_time FROM jails WHERE jail_id = :id");
        $stmt->bindParam("id", $id);
        $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        return $res["jail_time"];
    }

    public function hasTimePassed() : bool
    {
        return $this->getRemainingTime() <= time();
    }

    public function isJailed(string $member) : bool
    {
        return strtolower($member) == $this->getMember();
    }


    public function getMember() : ?string
    {
        $id = $this->getId();

        $stmt = Main::getDb()->prepare("SELECT jail_member FROM jails WHERE jail_id");
        $stmt->bindParam("jail_id", $id);
        $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        return $res["jail_member"];

    }

    /**
     * TODO: dit moet stricter zijn! Dus ook zoeken op basis van BoundingBox en van Level.
     *
     * @return int
     */
    public function getId() : int
    {
        $name = $this->name;

        $stmt = Main::getDb()->prepare("SELECT jail_id FROM jails WHERE lower(jail_name) = lower(:jail_name)");
        $stmt->bindParam("jail_name", $name);
        $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        $stmt->close();

        return $res["jail_id"];
    }


}