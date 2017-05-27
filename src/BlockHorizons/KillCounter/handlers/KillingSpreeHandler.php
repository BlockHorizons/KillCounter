<?php

namespace BlockHorizons\KillCounter\handlers;

use BlockHorizons\KillCounter\events\player\killingspree\PlayerKillingSpreeEndEvent;
use BlockHorizons\KillCounter\events\player\killingspree\PlayerKillingSpreeStartEvent;
use BlockHorizons\KillCounter\KillingSpree;
use BlockHorizons\KillCounter\Loader;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;

class KillingSpreeHandler extends BaseHandler {

	private $currentKills = [];
	/** @var KillingSpree[] */
	private $killingSpree = [];

	private $latestKill = [];

	public function __construct(Loader $loader) {
		parent::__construct($loader);

		if(!file_exists($loader->getDataFolder() . "killingsprees.yml")) {
			return;
		}
		$killingSprees = yaml_parse_file($loader->getDataFolder() . "killingsprees.yml");
		foreach($killingSprees as $name => $killingSpree) {
			$killingSpree = unserialize($killingSpree);
			$this->killingSpree[$name] = $killingSpree;
		}
		unlink($loader->getDataFolder() . "killingsprees.yml");
	}

	public function save() {
		$data = [];
		foreach($this->killingSpree as $player => $spree) {
			$data[$player] = serialize($spree);
		}
		yaml_emit_file($this->getLoader()->getDataFolder() . "killingsprees.yml", $data);
	}

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function addPlayer(Player $player): bool {
		if(isset($this->currentKills[$player->getname()])) {
			return false;
		}
		$this->currentKills[$player->getName()] = 0;
		return true;
	}

	/**
	 * @param Player $player
	 *
	 * @return int
	 */
	public function getCurrentKills(Player $player): int {
		return $this->currentKills[$player->getName()];
	}

	/**
	 * @param Player $player
	 */
	public function clearCurrentKills(Player $player) {
		unset($this->currentKills[$player->getName()]);
	}

	/**
	 * @param Player $player
	 * @param int    $amount
	 */
	public function addKills(Player $player, Player $victim, int $amount = 1) {
		$this->addPlayer($player);
		$this->currentKills[$player->getName()] += $amount;

		if(isset($this->latestKill[$player->getName()]) && $this->getLoader()->getConfig()->get("Enable-Multi-Kill")) {
			if(time() - $this->getLatestKill($player)["time"] <= $this->getLoader()->getConfig()->get("Multi-Kill-Time")) {
				switch($this->getLatestKill($player)["kills"]) {
					default:
					case 1:
						$type = "Double Kill! ";
						break;
					case 2:
						$type = "Triple Kill! ";
						break;
					case 3:
						$type = "Quadra Kill! ";
						break;
					case 4:
						$type = "Penta Kill! ";
						break;
					case 5:
						$type = "Hexa Kill! ";
						unset($this->latestKill[$player->getName()]);
						break;
				}
				$message = TF::RED . TF::BOLD . $type . TF::RESET . TF::YELLOW . $player->getDisplayName() . TF::RESET . TF::YELLOW . " has slain " . TF::RED . $victim->getDisplayName() . TF::YELLOW . "!";
				$this->getLoader()->getServer()->broadcastMessage($message);
			} else {
				unset($this->latestKill[$player->getName()]);
			}
		}
		$this->setLatestKill($player);

		if($this->getCurrentKills($player) >= $this->getRequiredKills()) {
			$this->startKillingSpree($player);
		}
	}

	/**
	 * @param Player $player
	 *
	 * @return int
	 */
	public function getKills(Player $player): int {
		return $this->currentKills[$player->getName()];
	}

	/**
	 * @return int
	 */
	public function getRequiredKills(): int {
		return $this->getLoader()->getConfig()->get("Kills-For-Killing-Spree");
	}

	/**
	 * @param Player $player
	 * @param int    $kills
	 *
	 * @return bool
	 */
	public function startKillingSpree(Player $player, int $kills = 0): bool {
		if(isset($this->killingSpree[$player->getName()])) {
			return false;
		}
		$this->getLoader()->getServer()->getPluginManager()->callEvent($ev = new PlayerKillingSpreeStartEvent($this->getLoader(), $player, $kills + $this->getCurrentKills($player), $kills));
		if($ev->isCancelled()) {
			return false;
		}

		$this->currentKills[$player->getName()] = $ev->getTotalKills() - $ev->getStartingKills();
		$this->killingSpree[$player->getName()] = new KillingSpree($player, $ev->getStartingKills());
		return true;
	}

	/**
	 * @param Player $player
	 *
	 * @return KillingSpree
	 */
	public function getKillingSpree(Player $player): KillingSpree {
		return $this->killingSpree[$player->getName()];
	}

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function hasKillingSpree(Player $player): bool {
		return isset($this->killingSpree[$player->getName()]);
	}

	/**
	 * @param Player $player
	 * @param Player $killer
	 *
	 * @return bool
	 */
	public function endKillingSpree(Player $player, Player $killer): bool {
		if($this->hasKillingSpree($player)) {
			$this->getLoader()->getServer()->getPluginManager()->callEvent($ev = new PlayerKillingSpreeEndEvent($this->getLoader(), $player, $this->getKillingSpree($player)->getTotalKills(), $this->getKillingSpree($player)->getKills()));
			if($ev->isCancelled()) {
				return false;
			}
			$this->getLoader()->getServer()->broadcastMessage(TF::YELLOW . TF::BOLD . "Shut down! " . TF::RESET . TF::AQUA . $player->getDisplayName() . " was killed by " . $killer->getDisplayName());
			$this->getLoader()->getServer()->broadcastMessage(TF::YELLOW . "The killing spree of " . TF::RED . $player->getDisplayName() . TF::YELLOW . " has ended, with a total of " . TF::RED . $this->killingSpree[$player->getName()]->getTotalKills() . " kills!");
			unset($this->killingSpree[$player->getName()]);
			$this->clearCurrentKills($player);
			return true;
		}
		return false;
	}

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function setLatestKill(Player $player): bool {
		$this->latestKill[$player->getName()] = [
			"time" => time(),
			"kills" => (isset($this->latestKill[$player->getName()]) ? $this->latestKill[$player->getName()]["kills"] + 1 : 1)
		];
		return true;
	}

	/**
	 * @param Player $player
	 *
	 * @return array
	 */
	public function getLatestKill(Player $player) {
		return $this->latestKill[$player->getName()];
	}
}