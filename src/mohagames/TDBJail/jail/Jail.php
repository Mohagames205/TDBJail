<?php

namespace mohagames\TDBJail\jail;

use mohagames\TDBJail\Main;
use mohagames\TDBJail\tile\LootChest;
use mohagames\TDBJail\util\Helper;
use mohagames\TDBJail\util\InventoryHelper;
use pocketmine\level\Level;
use pocketmine\level\Position;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\OfflinePlayer;
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\tile\Chest;
use pocketmine\tile\Tile;
use SQLite3Stmt;

class Jail
{

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
     * @var string|null
     */
    private $member;

    /**
     * @var Chest
     */
    private $lootChest;

    public function __construct(string $name, AxisAlignedBB $boundingBox, Level $level, ?Vector3 $spawn = null, string $member = null, Chest $lootChest = null)
    {
        $this->name = $name;
        $this->boundingBox = $boundingBox;
        $this->level = $level;
        $this->spawn = $spawn;
        $this->member = $member;
        $this->lootChest = $lootChest;
    }

    public function getBoundingBox(): AxisAlignedBB
    {
        return $this->boundingBox;
    }

    public function getSpawn(): Vector3
    {
        $bb = $this->boundingBox;

        $defaultSpawn = Helper::arrayToVector([($bb->maxX + $bb->minX) / 2, $bb->minY + 1, ($bb->maxZ + $bb->minZ) / 2]);

        return $this->spawn ?? $defaultSpawn;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getLootChest() : Chest
    {
        return $this->lootChest;
    }

    public function setLootChest(Chest $lootChest) : void
    {
        $lootChestLocation = json_encode([$lootChest->getX(), $lootChest->getY(), $lootChest->getZ()]);
        $id = $this->getId();

        /** @var SQLite3Stmt $stmt */
        $stmt = Main::getDb()->prepare("UPDATE jails SET jail_chest = :chest WHERE jail_id = :jail_id");
        $stmt->bindParam("chest", $lootChestLocation);
        $stmt->bindParam("jail_id", $id);
        $stmt->execute();
        $stmt->close();

        $lootChestVector = $lootChest->asVector3();
        $oldPairedTile = $lootChest->getPair();

        /// tile1
        $this->getLevel()->removeTile($lootChest);
        $tile = Tile::createTile("LootChest", $this->getLevel(), LootChest::createNBT($lootChestVector));
        $this->getLevel()->addTile($tile);
        ///

        /** @var LootChest $chestTile */
        $chestTile = $this->getLevel()->getTile($lootChestVector);

        if($oldPairedTile !== null)
        {
            $oldPairVector = $oldPairedTile->asVector3();
            $this->getLevel()->removeTile($oldPairedTile);
            $chestTile->unpair();
            $pairTile = Tile::createTile("LootChest", $this->getLevel(), LootChest::createNBT($oldPairVector));
            $chestTile->pairWith($pairTile);
        }

        $this->lootChest = $chestTile;
    }

    public function setSpawn(Vector3 $spawn) : void
    {
        $id = $this->getId();
        $encodedSpawn = json_encode(Helper::vectorToArray($spawn));
        //db query

        /** @var SQLite3Stmt $stmt */
        $stmt = Main::getDb()->prepare("UPDATE jails SET jail_spawn = :spawn WHERE jail_id = :jail_id");
        $stmt->bindParam("spawn", $encodedSpawn);
        $stmt->bindParam("jail_id", $id);
        $stmt->execute();
        $stmt->close();

        $this->spawn = $spawn;
    }

    public function getLevel(): Level
    {
        return $this->level;
    }

    public function delete(): void
    {
        $id = $this->getId();

        /** @var SQLite3Stmt $stmt */
        $stmt = Main::getDb()->prepare("DELETE FROM jails WHERE jail_id = :id");
        $stmt->bindParam("id", $id);
        $stmt->execute();
        $stmt->close();
    }

    /**
     * @param int $time
     */
    public function setTime(int $time) : void
    {
        $id = $this->getId();

        /** @var SQLite3Stmt $stmt */
        $stmt = Main::getDb()->prepare("UPDATE jails SET jail_time = :time WHERE jail_id = :jail_id");
        $stmt->bindParam("time", $time);
        $stmt->bindParam("jail_id", $id);
        $stmt->execute();
        $stmt->close();
    }

    public function setMember(string $member, int $time): bool
    {
        if (!$this->isJailed($member)) {
            if (Helper::playerExists($member)) {
                $this->member = strtolower($member);

                $id = $this->getId();

                /** @var SQLite3Stmt $stmt */
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

    public function deleteMember() : bool
    {
        $id = $this->getId();
        $this->member = null;

        /** @var SQLite3Stmt $stmt */
        $stmt = Main::getDb()->prepare("UPDATE jails SET jail_member = NULL WHERE jail_id = :jail_id");
        $stmt->bindParam("jail_id", $id);
        $stmt->execute();
        $stmt->close();

        return true;
    }

    public function getRemainingTime() : int
    {
        $id = $this->getId();

        /** @var SQLite3Stmt $stmt */
        $stmt = Main::getDb()->prepare("SELECT jail_time FROM jails WHERE jail_id = :id");
        $stmt->bindParam("id", $id);

        /** @var array $res */
        $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);

        return $res["jail_time"];
    }

    public function hasTimePassed(): bool
    {
        return $this->getRemainingTime() <= time();
    }

    public function isJailed(string $member): bool
    {
        return strtolower($member) == $this->getMember();
    }


    public function getMember(): ?string
    {
        return $this->member;
    }

    /**
     * TODO: dit moet stricter zijn! Dus ook zoeken op basis van BoundingBox en van Level.
     *
     * @return int
     */
    public function getId(): int
    {
        $name = $this->name;

        /** @var SQLite3Stmt $stmt */
        $stmt = Main::getDb()->prepare("SELECT jail_id FROM jails WHERE lower(jail_name) = lower(:jail_name)");
        $stmt->bindParam("jail_name", $name);

        /** @var array $res */
        $res = $stmt->execute()->fetchArray(SQLITE3_ASSOC);
        $stmt->close();

        return $res["jail_id"];
    }


}