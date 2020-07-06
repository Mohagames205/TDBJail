<?php


namespace mohagames\TDBJail\util;



use Exception;
use mohagames\TDBJail\jail\JailController;
use pocketmine\Player;

class InventoryQueue
{

    /**
     * @var array
     */
    public static $queue;

    public static function add(string $player) : void
    {
        if(self::isQueued($player)) throw new \LogicException("The player is already queued");

        self::$queue[strtolower($player)] = true;

    }

    public static function remove(string $player) : void
    {
        if(!self::isQueued($player)) throw new \LogicException("The player is not queued");

        unset(self::$queue[strtolower($player)]);
    }

    public static function isQueued(string $player) : bool
    {
        return isset(self::$queue[strtolower($player)]);
    }

    public static function handle(Player $player) : void
    {
        if(!self::isQueued($player->getName())) throw new \LogicException("The player has to be queued");
        $jail = JailController::getJailByMember($player->getName());
        if(!is_null($jail))
        {
            InventoryHelper::transferToInventory($player->getInventory(), $jail->getLootChest()->getInventory());
        }
        self::remove($player->getName());
    }


}