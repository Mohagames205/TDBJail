<?php

namespace mohagames\TDBJail\event;

use mohagames\TDBJail\jail\JailController;
use mohagames\TDBJail\Main;
use mohagames\TDBJail\util\Helper;
use mohagames\TDBJail\util\InventoryQueue;
use pocketmine\block\Chest;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerRespawnEvent;
use pocketmine\level\Position;

class EventListener implements Listener
{

    public function onFirstPosition(BlockBreakEvent $e) : void
    {
        if (Helper::isCorrectItem($e->getItem())) {
            $player = $e->getPlayer();
            Main::$firstPos[$player->getName()] = $e->getBlock()->asVector3();
            $e->setCancelled();

            $player->sendMessage("§f[§cTDBJail§f] §aEerste locatie geselecteerd");
        }
    }

    public function onSecondPosition(PlayerInteractEvent $e) : void
    {
        if (Helper::isCorrectItem($e->getItem())) {
            if ($e->getAction() == PlayerInteractEvent::RIGHT_CLICK_BLOCK) {
                $player = $e->getPlayer();
                Main::$secondPos[$player->getName()] = $e->getBlock()->asVector3();
                $e->setCancelled();

                $player->sendMessage("§f[§cTDBJail§f] §aTweede locatie geselecteerd.");
            }

        }

    }

    public function onSetChestInteract(BlockBreakEvent $e) : void
    {
        $player = $e->getPlayer();
        if (isset(Main::$setChestSession[$player->getName()])) {
            if($e->getBlock() instanceof Chest)
            {
                $jail = Main::$setChestSession[$player->getName()];
                $chestTile = $e->getBlock()->getLevel()->getTile($e->getBlock());
                if($chestTile instanceof \pocketmine\tile\Chest)
                {
                    var_dump($chestTile instanceof \pocketmine\tile\Chest);
                    $jail->setLootChest($chestTile);
                    $player->sendMessage("§f[§cTDBJail§f] §aDe lootchest is succesvol ingesteld.");
                }
            }
            $e->setCancelled();
            unset(Main::$setChestSession[$player->getName()]);
        }
    }

    public function onDeath(PlayerRespawnEvent $e) : void
    {
        $jail = JailController::getJailByMember($e->getPlayer()->getName());
        if (!is_null($jail)) {
            $spawn = $jail->getSpawn();
            $e->setRespawnPosition(new Position($spawn->getX(), $spawn->getY(), $spawn->getZ(), $jail->getLevel()));
        }
    }

    public function onJoin(PlayerJoinEvent $e) : void
    {
        $jail = JailController::getJailByMember($e->getPlayer()->getName());
        if (!is_null($jail)) {
            $playerJail = JailController::getJailAtPosition($e->getPlayer());
            if (is_null($playerJail) || $playerJail->getId() != $jail->getId()) {
                $e->getPlayer()->teleport($jail->getSpawn());
            }
        }
    }

    public function onQueuedJoin(PlayerJoinEvent $e) : void
    {
        $player = $e->getPlayer();
        if(InventoryQueue::isQueued($player->getName())) InventoryQueue::handle($player);
    }


}