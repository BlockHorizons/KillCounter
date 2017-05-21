<?php

namespace BlockHorizons\KillCounter\handlers;

use BlockHorizons\KillCounter\KillingSpree;
use BlockHorizons\KillCounter\Loader;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;

class KillingSpreeHandler extends BaseHandler {

	private $currentKills = [];
	private $killingSpree = [];

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
	public function addKills(Player $player, int $amount = 1) {
		$this->addPlayer($player);
		$this->currentKills[$player->getName()] += $amount;

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
		$this->killingSpree[$player->getName()] = new KillingSpree($player, $kills);
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
	 *
	 * @return bool
	 */
	public function endKillingSpree(Player $player, Player $killer): bool {
		if($this->hasKillingSpree($player)) {
			$this->getLoader()->getServer()->broadcastMessage(TF::YELLOW . TF::BOLD . "Shut down! " . TF::RESET . TF::AQUA . $player->getName() . " was killed by " . $killer->getName());
			$this->getLoader()->getServer()->broadcastMessage(TF::YELLOW . "The killing spree of " . TF::RED . $player->getName() . TF::YELLOW . " has ended, with a total of " . TF::RED . $this->killingSpree[$player->getName()]->getKills() . " kills!");
			unset($this->killingSpree[$player->getName()]);
			$this->clearCurrentKills($player);
			return true;
		}
		return false;
	}
}