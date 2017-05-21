<?php

namespace BlockHorizons\KillCounter;

use pocketmine\Player;
use pocketmine\Server;
use pocketmine\utils\TextFormat as TF;

class KillingSpree {

	private $player;
	private $kills;

	public function __construct(Player $player, int $kills = 0) {
		$this->player = $player->getName();
		$this->kills = $kills;
	}

	/**
	 * @return Loader
	 */
	public function getLoader(): Loader {
		$loader = Server::getInstance()->getPluginManager()->getPlugin("KillCounter");
		if($loader instanceof Loader) {
			return $loader;
		}
		return null;
	}

	/**
	 * @return Player
	 */
	public function getPlayer(): Player {
		return Server::getInstance()->getPlayer($this->player);
	}

	/**
	 * @return int
	 */
	public function getKills(): int {
		return $this->kills;
	}

	/**
	 * @param int $kills
	 */
	public function setKills(int $kills) {
		$this->kills = $kills;
	}

	/**
	 * @param int $amount
	 */
	public function addKills(int $amount = 1) {
		$this->kills += $amount;

		$totalKills = $this->kills + $this->getLoader()->getConfig()->get("Kills-For-Killing-Spree", 5) - 1;
		Server::getInstance()->broadcastMessage(TF::YELLOW . $this->getPlayer()->getName() . " is on a killing spree of " . TF::RED . TF::BOLD . $totalKills . TF::RESET . TF::YELLOW . " kills!");
	}
}