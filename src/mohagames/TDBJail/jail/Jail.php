<?php

namespace mohagames\TDBJail\jail;

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
     * @var array
     */
    private $members;

    public function __construct(string $name, AxisAlignedBB $boundingBox, Level $level, ?Vector3 $spawn = null, array $members = [])
    {
        $this->name = $name;
        $this->boundingBox = $boundingBox;
        $this->level = $level;
        $this->spawn = $spawn;
        $this->members = $members;
    }

    public function getBoundingBox() : AxisAlignedBB
    {
        return $this->boundingBox;
    }

    public function getSpawn() : Vector3
    {
        $bb = $this->boundingBox;

        $defaultSpawn = Helper::arrayToVector([($bb->maxX - $bb->minX)/2,$bb->minY, ($bb->maxZ - $bb->minZ)/2]);

        return $this->spawn ?? $defaultSpawn;
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

    }

    public function addMember(string $member)
    {


    }

    public function removeMember(string $member)
    {


    }

    public function isJailed(string $member) : bool
    {
        return in_array(strtolower($member), $this->getMembers());
    }


    public function getMembers() : array
    {
        $id = $this->getId();

        $stmt = Main::getDb()->prepare("SELECT jail_members FROM jails WHERE jail_id = :jail_id");
        $stmt->bindParam("jail_id", $id);
        $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        $stmt->close();

        return json_decode($res["jail_members"], true);
    }

    /**
     * TODO: dit moet stricter zijn! Dus ook zoeken op basis van BoundingBox en van Level.
     *
     * @return int
     */
    public function getId() : int
    {
        $stmt = Main::getDb()->prepare("SELECT jail_id FROM jails WHERE lower(jail_name) = lower(:jail_name)");
        $stmt->bindParam("jail_name", $name);
        $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        $stmt->close();

        return $res["jail_id"];
    }


}