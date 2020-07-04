<?php


namespace mohagames\TDBJail\form;


use jojoe77777\FormAPI\CustomForm;
use mohagames\TDBJail\jail\Jail;
use mohagames\TDBJail\jail\JailController;
use mohagames\TDBJail\Main;
use mohagames\TDBJail\util\Helper;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class JailForm
{
    public static function openJailUI(Player $player, Jail $jail) : void
    {

        $form = new CustomForm(function (Player $player, array $data = null) use ($jail) {
            if (is_null($data)) return;
            $result = $data[0];
            if ($result === null) {
                return;
            }


            $now = time();
            $day = ($data[1] * 86400);
            $hour = ($data[2] * 3600);
            if ($data[3] > 1) {
                $min = ($data[3] * 60);
            } else {
                $min = 60;
            }
            $banTime = $now + $day + $hour + $min;

            $target = $data[0];
            $jailedPlayer = Main::getInstance()->getServer()->getPlayerExact($target);

            if (!Helper::playerExists($target)) {
                $player->sendMessage("§f[§cTDBJail§f] §cDeze speler bestaat niet.");
                return;
            }

            if (!is_null(JailController::getJailByMember($target)) || $jail->isJailed($target)) {
                $player->sendMessage("§f[§cTDBJail§f] §cDeze speler is al toegevoegd.");
                return;
            }

            if ($jail->setMember($target, $banTime)) {
                if (!is_null($jailedPlayer)) {
                    $jailedPlayer->sendMessage("§f[§cTDBJail§f] §aU bent gejailed door §2" . $player->getName() . "§a.");
                    $jailedPlayer->teleport($jail->getSpawn());
                }
                $player->sendMessage("§f[§cTDBJail§f] §aDe speler is succesvol toegevoegd!");
                return;
            }

            $player->sendMessage("§f[§cTDBJail§f] §cEr is iets misgelopen!");
            return;
        });
        $form->setTitle(TextFormat::BOLD . "JailUI");
        $form->addInput("Playername", "Mohagames205");
        $form->addSlider("Day/s", 0, 30, 1, 0);
        $form->addSlider("Hour/s", 0, 24, 1, 0);
        $form->addSlider("Minute/s", 0, 60, 5, 0);
        $player->sendForm($form);
    }


}