<?php
declare(strict_types=1);

namespace ElectroGames\FloatingText;

use pocketmine\Player;
use pocketmine\utils\Config;
use pocketmine\math\Vector3;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;
use pocketmine\level\particle\FloatingTextParticle;

class Main extends PluginBase {

    /** @var array FloatingTexts[] */
    public $floatingTexts = [];

    public function onEnable() {
        $this->saveDefaultConfig();
        $this->floatingText = new Config($this->getDataFolder() . "floating-text.yml", Config::YAML);
        $this->getServer()->getCommandMap()->register(strtolower($this->getName()), new FloatingTextCommand($this));
        $this->getScheduler()->scheduleRepeatingTask(new FloatingTextUpdate($this), 20 * $this->getUpdateTime());
        $this->reloadFloatingText();
    }
    
    public function reloadFloatingText() {
        foreach($this->getFloatingTexts()->getAll() as $id => $array) {
            $this->floatingTexts[$id] = new FloatingTextParticle(new Vector3($array["x"], $array["y"], $array["z"]), "");
        }
    }
    
    /**
     * @param Player $player
     * @param string $string
     * @return string
     */
    public function replaceProcess(Player $player, string $string): string {
        $string = str_replace("{line}", TF::EOL, $string);
        $string = str_replace("{player_name}", $player->getName(), $string);
        $string = str_replace("{player_health}", round($player->getHealth(), 1), $string);
        $string = str_replace("{player_max_health}", $player->getMaxHealth(), $string);
        $string = str_replace("{online_players}", count($this->getServer()->getOnlinePlayers()), $string);
        $string = str_replace("{online_max_players}", $this->getServer()->getMaxPlayers(), $string);
        return $string;
    }
    
    /**
     * @return Config
     */
    public function getFloatingTexts(): Config {
        return $this->floatingText;
    }
    
    /**
     * @return int
     */
    public function getUpdateTime(): int {
        return $this->getConfig()->get("update-time");
    }
}
