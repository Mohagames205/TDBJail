<?php


namespace mohagames\TDBJail\task;


use mohagames\TDBJail\jail\JailController;
use pocketmine\Player;
use pocketmine\scheduler\Task;
use pocketmine\Server;

class CheckJailedPlayerTask extends Task
{

    public function onRun(int $currentTick)
    {
        foreach (Server::getInstance()->getOnlinePlayers() as $onlinePlayer)
        {
            $playerJailAtPos = JailController::getJailAtPosition($onlinePlayer);
            $playerJail = JailController::getJailByMember($onlinePlayer->getName());

            if(is_null($playerJail)) return;

            if(is_null($playerJailAtPos))
            {
                $onlinePlayer->teleport($playerJail->getSpawn());
            }

            if($playerJail->hasTimePassed())
            {
                $onlinePlayer->teleport($onlinePlayer->getLevel()->getSafeSpawn());
                $playerJail->deleteMember();
            }
        }
    }

}