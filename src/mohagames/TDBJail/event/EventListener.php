<?php

namespace mohagames\TDBJail\event;

use mohagames\TDBJail\jail\JailController;
use mohagames\TDBJail\Main;
use mohagames\TDBJail\util\Helper;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerMoveEvent;
use pocketmine\event\player\PlayerQuitEvent;

class EventListener implements Listener
{

    public function onFirstPosition(BlockBreakEvent $e)
    {
        if(Helper::isCorrectItem($e->getItem()))
        {
            $player = $e->getPlayer();
            Main::$firstPos[$player->getName()] = $e->getBlock()->asVector3();
            $e->setCancelled();

            $player->sendMessage("§f[§cTDBJail§f] §aEerste locatie geselecteerd");
        }
    }

    public function onSecondPosition(PlayerInteractEvent $e)
    {
        if(Helper::isCorrectItem($e->getItem()))
        {
            if($e->getAction() == PlayerInteractEvent::RIGHT_CLICK_BLOCK)
            {
                $player = $e->getPlayer();
                Main::$secondPos[$player->getName()] = $e->getBlock()->asVector3();
                $e->setCancelled();

                $player->sendMessage("§f[§cTDBJail§f] §aTweede locatie geselecteerd.");
            }

        }

    }

    public function onDeath(PlayerDeathEvent $e)
    {
        $jail = JailController::getJailByMember($e->getPlayer()->getName());
        if(!is_null($jail))
        {
            $e->getPlayer()->teleport($jail->getSpawn());
        }
    }

    public function onJoin(PlayerJoinEvent $e)
    {
        $jail = JailController::getJailByMember($e->getPlayer()->getName());
        if(!is_null($jail))
        {
            $playerJail = JailController::getJailAtPosition($e->getPlayer());
            if(is_null($playerJail) || $playerJail->getId() != $jail->getId())
            {
                $e->getPlayer()->teleport($jail->getSpawn());
            }
        }
    }




}