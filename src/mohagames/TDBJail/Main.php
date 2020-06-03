<?php

declare(strict_types=1);

namespace mohagames\TDBJail;

use mohagames\TDBJail\event\EventListener;
use mohagames\TDBJail\jail\JailController;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\item\ItemFactory;
use pocketmine\item\ItemIds;
use pocketmine\math\AxisAlignedBB;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase {

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
     * @var \SQLite3
     */
    private static $db;

    public function onEnable()
    {
        $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this);

        self::$db = new \SQLite3($this->getDataFolder() . "Jail.db");
        self::$db->query("CREATE TABLE IF NOT EXISTS jails(jail_id INTEGER PRIMARY KEY AUTO_INCREMENT NOT NULL, jail_name TEXT, jail_bb TEXT, jail_level TEXT, jail_spawn TEXT, jail_members TEXT)");

        $config = new Config($this->getDataFolder() . "config.yml", Config::YAML, ["item_id" => ItemIds::GOLD_AXE, "lore" => "§cJailCreator"]);
        $config->save();

        self::$instance = $this;
    }


    public function onCommand(CommandSender $sender, Command $command, string $label, array $args): bool
    {
        if($command->getName() == "jail")
        {
            if(!isset($args[0]))
            {
                $sender->sendMessage("§4Gelieve de juiste command in te geven");
                return false;
            }

            switch($args[0])
            {
                case "wand":
                    $config = $this->getConfig()->getAll();
                    $item_id = $config["item_id"];
                    $lore = $config["lore"];

                    $item = ItemFactory::get($item_id);
                    $item->setLore([$lore]);

                    $sender->getInventory()->addItem($item);
                    $sender->sendMessage("§f[§cTDBJail§f] §aU heeft een §cJailWand §aontvangen");

                    break;

                case "save":
                    if(!isset(self::$firstPos[$sender->getName()]) || !isset(self::$secondPos[$sender->getName()]))
                    {
                        $sender->sendMessage("§f[§cTDBJail§f] §cU moet beide locaties instellen met de §cJailWand§a.");
                        return true;
                    }

                    if(!isset(self::$spawnPos[$sender->getName()]))
                    {
                        $sender->sendMessage("§f[§cTDBJail§f] §cU moet een spawnlocactie instellen met §4/jail setspawn§c.");
                        return true;
                    }

                    if(!isset($args[1]))
                    {
                        $sender->sendMessage("§f[§cTDBJail§f] §cGelieve een jailnaam in te geven.");
                        return true;
                    }

                    $pos1 = self::$firstPos[$sender->getName()];
                    $pos2 = self::$secondPos[$sender->getName()];

                    $boundingBox = new AxisAlignedBB(min($pos1->getX(), $pos2->getX()), min($pos1->getY(), $pos2->getY()), min($pos1->getZ(), $pos2->getZ()), max($pos1->getX(), $pos2->getX()), max($pos1->getY(), $pos2->getY()), max($pos1->getZ(), $pos2->getZ()));
                    $jailName = $args[1];

                    JailController::createJail($jailName, $boundingBox, $sender->getLevel(), $spawn);


                    break;

                case "add":

                    break;

                case "remove":

                    break;

                case "setspawn":



                    break;


                default:
                    return false;


            }
            return false;
        }


    }

    public static function getDb() : \SQLite3
    {
        return self::$db;
    }

    public static function getInstance() : self
    {
        return self::$instance;
    }

}
