<?php

namespace mohagames\TDBJail\event;

use mohagames\TDBJail\Main;
use mohagames\TDBJail\util\Helper;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;

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
            $player = $e->getPlayer();
            Main::$secondPos[$player->getName()] = $e->getBlock()->asVector3();
            $e->setCancelled();

            $player->sendMessage("§f[§cTDBJail§f] §aTweede locatie geselecteerd.");
        }

    }



}