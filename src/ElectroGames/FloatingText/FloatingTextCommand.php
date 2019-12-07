<?php
declare(strict_types=1);

namespace ElectroGames\FloatingText;

use pocketmine\Player;
use pocketmine\plugin\Plugin;
use pocketmine\math\Vector3;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\level\particle\FloatingTextParticle;
use pocketmine\utils\TextFormat as TF;

class FloatingTextCommand extends Command {

    /** @var Main */
    private $plugin;

    /**
     * @param Main $plugin
     */
    public function __construct(Main $plugin){
        parent::__construct("floatingtext", "FloatingText command", "/floatingtext spawn|edit|remove|move|list", ["ft"]);
        $this->setPermission("ft.command.admin");
        $this->plugin = $plugin;
    }

    /**
     * @param CommandSender $sender
     * @param string $commandLabel
     * @param array $args
     * @return bool
     */
    public function execute(CommandSender $sender, string $commandLabel, array $args) : bool{
        $senderName = $sender->getName();
        $texts = (array)$this->getPlugin()->getConfig()->get("texts");
        $floatingTexts = (array)$this->getPlugin()->getFloatingTexts()->getAll();
        if(!$this->testPermission($sender)) {
            return false;
        }
        if(!isset($args[0])) {
            $sender->sendMessage(TF::WHITE . "Usage: /floatingtext spawn|edit|remove|move|list");
            return false;
        }
        switch($args[0]) {
            case "spawn":
                if(!$sender instanceof Player) {
                    $sender->sendMessage(TF::RED . "You can use this command only in-game");
                    return false;
                }
                if(!isset($args[1])) {
                    $sender->sendMessage(TF::WHITE . "Usage: /foatingtext spawn text|config");
                    return false;
                }
                switch($args[1]) {
                    case "text":
                        if(!isset($args[2])) {
                            $sender->sendMessage(TF::WHITE . "Usage: /floatingtext spawn text {text}");
                            return false;
                        }
                        $id = rand(1, 1000000) + rand(1, 1000000);
                        $info = array(
                            "x" => $sender->getX(),
                            "y" => $sender->getY(),
                            "z" => $sender->getZ(),
                            "level" => $sender->getLevel()->getFolderName(),
                            "text" => implode(" ", array_slice($args, 2))
                        );
                        $this->getPlugin()->getFloatingTexts()->setNested("$id", $info);
                        $this->getPlugin()->getFloatingTexts()->save();
                        $this->getPlugin()->reloadFloatingText();
                        $sender->sendMessage(TF::RED . "FloatingText spawned with a ID: " . TF::YELLOW . $id);
                        break;
                    case "config":
                        if(!isset($args[2])) {
                            $sender->sendMessage(TF::WHITE . "Usage: /floatingtext spawn config {TextName}");
                            return false;
                        }
                        if(!isset($texts[$args[2]])) {
                            $sender->sendMessage(TF::YELLOW . $args[2] . TF::RED . " does not exist in the config");
                            return false;
                        }
                        $id = rand(1, 1000000) + rand(1, 1000000);
                        $info = array(
                            "x" => $sender->getX(),
                            "y" => $sender->getY(),
                            "z" => $sender->getZ(),
                            "level" => $sender->getLevel()->getFolderName(),
                            "text" => $texts[$args[2]]
                        );
                        $this->getPlugin()->getFloatingTexts()->setNested("$id", $info);
                        $this->getPlugin()->getFloatingTexts()->save();
                        $this->getPlugin()->reloadFloatingText();
                        $sender->sendMessage(TF::RED . "FloatingText spawned with a ID: " . TF::YELLOW . $id);
                        break;
                    default:
                        $sender->sendMessage(TF::WHITE . "Usage: /foatingtext spawn text|config");
                        break;
                }
                break;
            case "edit":
                if(!isset($args[1]) or !isset($args[2])) {
                    $sender->sendMessage(TF::WHITE . "Usage: /floatingtext edit {id} {text}");
                    return false;
                }
                if(!isset($floatingTexts[$args[1]])) {
                    $sender->sendMessage(TF::RED . "FloatingText with ID " . TF::YELLOW . $args[1] . TF::RED . " does not exist");
                    return false;
                }
                $text = implode(" ", array_slice($args, 2));
                $this->getPlugin()->getFloatingTexts()->setNested("$args[1].text", $text);
                $this->getPlugin()->getFloatingTexts()->save();
                $sender->sendMessage(TF::RED . "You have changed the text of this FloatingText [ID: " . TF::YELLOW . $args[1] . TF::RED . "]");
                break;
            case "remove":
                if(!isset($args[1])) {
                    $sender->sendMessage(TF::WHITE . "Usage: /floatingtext remove {id}");
                    return false;
                }
                if(!isset($floatingTexts[$args[1]])) {
                    $sender->sendMessage(TF::RED . "FloatingText with ID " . TF::YELLOW . $args[1] . TF::RED . " does not exist");
                    return false;
                }
                $level = $this->getPlugin()->getServer()->getLevelByName($this->getPlugin()->getFloatingTexts()->getNested("$args[1].level"));
                $ft = $this->getPlugin()->floatingTexts[$args[1]];
                $ft->setText("");
                $level->addParticle($ft);
                $this->getPlugin()->getFloatingTexts()->remove($args[1]);
                $this->getPlugin()->getFloatingTexts()->save();
                unset($this->getPlugin()->floatingTexts[$args[1]]);
                $sender->sendMessage(TF::RED . "You have removed this FloatingText [ID: " . TF::YELLOW . $args[1] . TF::RED . "]");
                break;
            case "move":
                if(!$sender instanceof Player) {
                    $sender->sendMessage(TF::RED . "You can use this command only in-game");
                    return false;
                }
                if(!isset($args[1])) {
                    $sender->sendMessage(TF::WHITE . "Usage: /floatingtext move {id}");
                    return false;
                }
                if(!isset($floatingTexts[$args[1]])) {
                    $sender->sendMessage(TF::RED . "FloatingText with ID " . TF::YELLOW . $args[1] . TF::RED . " does not exist");
                    return false;
                }
                $this->getPlugin()->getFloatingTexts()->setNested("$args[1].x", $sender->getX());
                $this->getPlugin()->getFloatingTexts()->setNested("$args[1].y", $sender->getY());
                $this->getPlugin()->getFloatingTexts()->setNested("$args[1].z", $sender->getZ());
                $this->getPlugin()->getFloatingTexts()->save();
                $level = $this->getPlugin()->getServer()->getLevelByName($this->getPlugin()->getFloatingTexts()->getNested("$args[1].level"));
                $ft = $this->getPlugin()->floatingTexts[$args[1]];
                $ft->setText("");
                $level->addParticle($ft);
                unset($this->getPlugin()->floatingTexts[$args[1]]);
                $this->getPlugin()->floatingTexts[$args[1]] = new FloatingTextParticle(new Vector3($this->getPlugin()->getFloatingTexts()->getNested("$args[1].x"), $this->getPlugin()->getFloatingTexts()->getNested("$args[1].y"), $this->getPlugin()->getFloatingTexts()->getNested("$args[1].z")), "");
                $sender->sendMessage(TF::RED . "You have moved this FloatingText [ID: " . TF::YELLOW . $args[1] . TF::RED . "]");
                break;
            case "list":
                foreach($floatingTexts as $id => $array) {
                    $sender->sendMessage(TF::YELLOW . $id . ")" . TF::RED . " Level: " . TF::WHITE . $array["level"] . TF::BLUE . " | " . TF::RED . "Coords: " . TF::WHITE . round($array["x"], 0) . "|" . round($array["y"], 0) . "|" . round($array["z"], 0));
                }
                break;
            default:
                $sender->sendMessage(TF::WHITE . "Usage: /floatingtext spawn|edit|remove|move|list");
                break;
        }
        return true;
    }

    /**
     * @return Main
     */
    public function getPlugin() : Plugin{
        return $this->plugin;
    }
}
