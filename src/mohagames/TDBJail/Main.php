<?php

declare(strict_types=1);

namespace mohagames\TDBJail;

use mohagames\TDBJail\event\EventListener;
use mohagames\TDBJail\form\JailForm;
use mohagames\TDBJail\jail\JailController;
use mohagames\TDBJail\task\CheckJailedPlayerTask;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use SQLite3;

class Main extends PluginBase
{

    /**
     * @var Vector3[]
     */
    public static $firstPos;

    /**
     * @var Vector3[]
     */
    public static $secondPos;

    /**
     * @var Vector3[]
     */
    public static $spawnPos;

    /**
     * @var Main
     */
    private static $instance;

    /**
     * @var SQLite3
     */
    private static $db;

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);

        self::$db = new SQLite3($this->getDataFolder() . "Jail.db");
        self::$db->query("CREATE TABLE IF NOT EXISTS jails(jail_id INTEGER PRIMARY KEY AUTOINCREMENT, jail_name TEXT, jail_bb TEXT, jail_level TEXT, jail_spawn TEXT, jail_member TEXT, jail_time TEXT)");

        $config = new Config($this->getDataFolder() . "config.yml", Config::YAML, ["item_id" => ItemIds::GOLD_AXE, "lore" => "§cJailCreator"]);
        $config->save();

        $this->getScheduler()->scheduleDelayedRepeatingTask(new CheckJailedPlayerTask(), 20 * 5, 20 * 5);

        self::$instance = $this;
    }

    /**
     * @param CommandSender $sender
     * @param Command $command
     * @param string $label
     * @param array $args
     * @return bool
     */
    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if (!$sender instanceof Player) return true;
        if ($command->getName() == "jail") {
            if (!isset($args[0])) {
                $this->sendHelpMenu($sender);
                return true;
            }

            switch ($args[0]) {
                case "info":
                    if (!$sender->hasPermission("jail.admin.info")) {
                        $sender->sendMessage("§f[§cTDBJail§f] §cU heeft geen permissions om deze command te gebruiken.");
                        return true;
                    }

                    $jail = JailController::getJailAtPosition($sender);

                    if (is_null($jail)) {
                        $sender->sendMessage("§f[§cTDBJail§f] §cDeze cel bestaat niet!");
                        return true;
                    }
                    $jailName = $jail->getName();
                    $member = $jail->getMember() ?? "Geen gevangene";
                    $sender->sendMessage("§f---§cJail Info§f---\n§cNaam: §4$jailName\n§cGevangene: §4$member");

                    break;
                case "wand":
                    if (!$sender->hasPermission("jail.admin.wand")) {
                        $sender->sendMessage("§f[§cTDBJail§f] §cU heeft geen permissions om deze command te gebruiken.");
                        return true;
                    }

                    $config = $this->getConfig()->getAll();
                    $item_id = $config["item_id"];
                    $lore = $config["lore"];

                    $item = ItemFactory::get($item_id);
                    $item->setLore([$lore]);

                    $sender->getInventory()->addItem($item);
                    $sender->sendMessage("§f[§cTDBJail§f] §aU heeft een §cJailWand §aontvangen");

                    break;

                case "save":
                    if (!$sender->hasPermission("jail.admin.save")) {
                        $sender->sendMessage("§f[§cTDBJail§f] §cU heeft geen permissions om deze command te gebruiken.");
                        return true;
                    }

                    if (!isset(self::$firstPos[$sender->getName()]) || !isset(self::$secondPos[$sender->getName()])) {
                        $sender->sendMessage("§f[§cTDBJail§f] §cU moet beide locaties instellen met de §cJailWand§c.");
                        return true;
                    }

                    if (!isset($args[1])) {
                        $sender->sendMessage("§f[§cTDBJail§f] §cGelieve een jailnaam in te geven.");
                        return true;
                    }

                    if (!is_null(JailController::getJailByName($args[1]))) {
                        $sender->sendMessage("§f[§cTDBJail§f] §cEr bestaat al een jail met deze naam!");
                        return true;

                    }

                    $pos1 = self::$firstPos[$sender->getName()];
                    $pos2 = self::$secondPos[$sender->getName()];

                    $boundingBox = new AxisAlignedBB(min($pos1->getX(), $pos2->getX()), min($pos1->getY(), $pos2->getY()), min($pos1->getZ(), $pos2->getZ()), max($pos1->getX(), $pos2->getX()), max($pos1->getY(), $pos2->getY()), max($pos1->getZ(), $pos2->getZ()));
                    $jailName = $args[1];

                    JailController::createJail($jailName, $boundingBox, $sender->getLevel());

                    unset(self::$secondPos[$sender->getName()]);
                    unset(self::$firstPos[$sender->getName()]);

                    $sender->sendMessage("§f[§cTDBJail§f] §aDe jail is succesvol aangemaakt, gelieve een spawnplek in te stellen!");
                    break;

                case "delete":
                    if (!$sender->hasPermission("jail.admin.delete")) {
                        $sender->sendMessage("§f[§cTDBJail§f] §cU heeft geen permissions om deze command te gebruiken.");
                        return true;
                    }

                    if (!isset($args[1])) {
                        $sender->sendMessage("§f[§cTDBJail§f] §cGelieve een celnaam in te geven.");
                        return true;
                    }

                    $jail = JailController::getJailByName($args[1]);

                    if (is_null($jail)) {
                        $sender->sendMessage("§f[§cTDBJail§f] §cDeze cel bestaat niet!");
                        return true;
                    }

                    $jail->delete();
                    $sender->sendMessage("§f[§cTDBJail§f] §aDe cel is succesvol verwijderd!");
                    break;

                case "setspawn":
                    if (!$sender->hasPermission("jail.admin.setspawn")) {
                        $sender->sendMessage("§f[§cTDBJail§f] §cU heeft geen permissions om deze command te gebruiken.");
                        return true;
                    }

                    $jail = JailController::getJailAtPosition($sender);
                    if (is_null($jail)) {
                        $sender->sendMessage("§f[§cTDBJail§f] §cU staat niet in een cel");
                        return true;
                    }

                    $jail->setSpawn($sender);
                    $sender->sendMessage("§f[§cTDBJail§f] §aDe spawn is succesvol ingesteld op uw locatie.");
                    break;

                default:
                    if (!$sender->hasPermission("jail.add")) {
                        $sender->sendMessage("§f[§cTDBJail§f] §cU heeft geen permissions om deze command te gebruiken.");
                        return true;
                    }

                    if (!isset($args[0]) || empty($args[0])) {
                        $sender->sendMessage("§f[§cTDBJail§f] §cGelieve een celnaam in te geven.");
                        return true;
                    }

                    $jail = JailController::getJailByName($args[0]);

                    if (is_null($jail)) {
                        $sender->sendMessage("§f[§cTDBJail§f] §cDeze cel bestaat niet!");
                        return true;
                    }

                    JailForm::openJailUI($sender, $jail);

                    return true;
            }
        } elseif ($command->getName() == "unjail") {
            if (!$sender->hasPermission("jail.remove")) {
                $sender->sendMessage("§f[§cTDBJail§f] §cU heeft geen permissions om deze command te gebruiken.");
                return true;
            }
            if (!isset($args[0])) {
                $sender->sendMessage("§f[§cTDBJail§f] §cGelieve een celnaam in te geven.");
                return true;
            }

            $jail = JailController::getJailByName($args[0]);

            if (is_null($jail)) {
                $sender->sendMessage("§f[§cTDBJail§f] §cDeze cel bestaat niet!");
                return true;
            }

            if (is_null($jail->getMember())) {
                $sender->sendMessage("§f[§cTDBJail§f] §cEr is niemand gejailed!");
                return true;
            }

            if ($jail->deleteMember()) {
                $sender->sendMessage("§f[§cTDBJail§f] §aDe speler is succesvol vrij gelaten");
                return true;
            }
            $sender->sendMessage("§f[§cTDBJail§f] §cEr is iets misgelopen!");
            return true;
        } elseif ($command->getName() == "jails") {
            $jails = JailController::getJails();
            $msg = "§f[§cTDBJail§f] §aHuidige cellen\n";
            foreach ($jails as $jail) {
                $remainingTime = $jail->getRemainingTime() - time();
                $day = floor($remainingTime / 86400);
                $hourSeconds = $remainingTime % 86400;
                $hour = floor($hourSeconds / 3600);
                $minuteSec = $hourSeconds % 3600;
                $minute = floor($minuteSec / 60);
                $remainingSec = $minuteSec % 60;
                $second = ceil($remainingSec);

                $gevangene = is_null($jail->getMember()) ? "§f| §cGeen gevangene" : $jail->getMember() . " §f| §a{d}§2d, §a{h}§2u, §a{m}§2m, §a{s}§2s";

                $msg .= str_replace(["{d}", "{h}", "{m}", "{s}"], [$day, $hour, $minute, $second], "§f- " . TextFormat::GREEN . $jail->getName() . "§f: §2" . $gevangene . "\n");
            }
            if (count($jails) == 0) {
                $msg = "§f[§cTDBJail§f] §aHuidige cellen\n§cEr zijn geen cellen";
            }

            $sender->sendMessage($msg);
            return true;


        } else {
            $this->sendHelpMenu($sender);
            return false;
        }
        return false;
    }

    public static function getDb(): SQLite3
    {

        return self::$db;
    }

    public static function getInstance(): self
    {
        return self::$instance;
    }

    private function sendHelpMenu(Player $sender)
    {
        $sender->sendMessage("§f[§cTDBJail§f] §cGelieve 1 van de beschikbare commands te gebruiken.");
        $sender->sendMessage("§f- §a/jail <jailnaam> §f|§o Jailed een speler in de gegeven cel.");
        $sender->sendMessage("§f- §a/jail info §f|§o Toont info over de cel waarop je staat.");
        $sender->sendMessage("§f- §a/jail wand §f|§o Geeft jouw  een jailwand.");
        $sender->sendMessage("§f- §a/jail save <jailnaam> §f|§o Slaagt de jail op");
        $sender->sendMessage("§f- §a/jail delete <jailnaam> §f|§o Delete de jail met de gegeven naam");
        $sender->sendMessage("§f- §a/jail setspawn §f|§o Stelt de spawn in in de jail waarin je staat.");
        $sender->sendMessage("§f- §a/unjail <jailnaam> §f|§o Unjailed de gevangen speler in de gegeven cel.");
    }

}
