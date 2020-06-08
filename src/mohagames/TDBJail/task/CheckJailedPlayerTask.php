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

            $remainingTime =  $playerJail->getRemainingTime() - time();
            $day = floor($remainingTime / 86400);
            $hourSeconds = $remainingTime % 86400;
            $hour = floor($hourSeconds / 3600);
            $minuteSec = $hourSeconds % 3600;
            $minute = floor($minuteSec / 60);
            $remainingSec = $minuteSec % 60;
            $second = ceil($remainingSec);

            $onlinePlayer->sendTip("§bU moet nog $day dagen, $hour uren, $minute minuten en $second seconden in de cel.");
        }
    }

}