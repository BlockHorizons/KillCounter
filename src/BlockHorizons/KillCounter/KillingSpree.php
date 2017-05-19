<?php

namespace BlockHorizons\KillCounter;

use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;

class KillingSpree {

	private $loader;
	private $player;
	private $kills;

	public function __construct(Loader $loader, Player $player, int $kills = 0) {
		$this->loader = $loader;
		$this->player = $player;
		$this->kills = $kills;
	}

	/**
	 * @return Loader
	 */
	public function getLoader(): Loader {
		return $this->loader;
	}

	/**
	 * @return Player
	 */
	public function getPlayer(): Player {
		return $this->player;
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

		$totalKills = $this->kills + $this->getLoader()->getConfig()->get("Kills-For-Killing-Spree", 5);
		$this->getLoader()->getServer()->broadcastMessage(TF::YELLOW . $this->getPlayer()->getName() . " is on a killing spree of " . TF::RED . TF::BOLD . $totalKills . TF::RESET . TF::YELLOW . " kills!");
	}
}