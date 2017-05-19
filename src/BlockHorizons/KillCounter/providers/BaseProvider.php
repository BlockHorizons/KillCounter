<?php

namespace BlockHorizons\KillCounter\providers;

use BlockHorizons\KillCounter\Loader;
use pocketmine\Player;

abstract class BaseProvider implements IProvider {

	private $loader;

	public function __construct(Loader $loader) {
		$this->loader = $loader;

		$this->initializeDatabase();
	}

	/**
	 * @return Loader
	 */
	public function getLoader(): Loader {
		return $this->loader;
	}

	/**
	 * @param $player
	 *
	 * @return string
	 */
	protected function turnToPlayerName($player): string {
		if($player instanceof Player) {
			return strtolower($player->getName());
		}
		return strtolower($player);
	}

	/**
	 * @param $player
	 *
	 * @return int
	 */
	public function getPlayerKills($player): int {
		return $this->getPlayerStats($player)["PlayerKills"];
	}

	/**
	 * @param $player
	 *
	 * @return int
	 */
	public function getPlayerAssists($player): int {
		return $this->getPlayerStats($player)["PlayerAssists"];
	}

	/**
	 * @param $player
	 *
	 * @return int
	 */
	public function getEntityKills($player): int {
		return $this->getPlayerStats($player)["EntityKills"];
	}

	/**
	 * @param $player
	 *
	 * @return int
	 */
	public function getDeaths($player): int {
		return $this->getPlayerStats($player)["Deaths"];
	}

	/**
	 * @param $player
	 *
	 * @return int
	 */
	public function getPoints($player): int {
		return $this->getPlayerStats($player)["Points"];
	}

	/**
	 * @param     $player
	 * @param int $amount
	 *
	 * @return bool
	 */
	public function addPoints($player, int $amount): bool {
		return $this->setPoints($player, $this->getPoints($player) + $amount);
	}

	/**
	 * @param     $player
	 * @param int $amount
	 * @param int $points
	 * @param int $money
	 *
	 * @return bool
	 */
	public function addPlayerKills($player, int $amount = 1, int $points = -1, int $money = -1): bool {
		$extraMoney = 0;
		$extraPoints = 0;
		if($this->getLoader()->getEventListener()->isOnKillingSpree($player)) {
			$killingSpree = $this->getLoader()->getEventListener()->getKillingSpree($player);
			$extraPoints = $killingSpree->getKills() * $this->getLoader()->getConfig()->get("Points-Added-Per-Spree-Kill");
			$extraMoney = $killingSpree->getKills() * $this->getLoader()->getConfig()->get("Money-Added-Per-Spree-Kill");
		}
		$this->addPoints($player, $points === -1 ? $this->getLoader()->getConfig()->get("Points-Per-Player-Kill") * $amount + $extraPoints: $points * $amount);
		if($this->getLoader()->isEconomyEnabled()) {
			$this->getLoader()->getEconomy()->addMoney($this->getLoader()->getServer()->getPlayer($player), $money === -1 ? $this->getLoader()->getConfig()->get("Money-Per-Player-Kill") * $amount + $extraMoney : $money * $amount);
		}
		return $this->setPlayerKills($player, $this->getPlayerKills($player) + $amount);
	}

	/**
	 * @param     $player
	 * @param int $amount
	 * @param int $points
	 * @param int $money
	 *
	 * @return bool
	 */
	public function addPlayerAssists($player, int $amount = 1, int $points = -1, int $money = -1): bool {
		$this->addPoints($player, $points === -1 ? $this->getLoader()->getConfig()->get("Points-Per-Player-Assist") * $amount : $points * $amount);
		if($this->getLoader()->isEconomyEnabled()) {
			$this->getLoader()->getEconomy()->addMoney($this->getLoader()->getServer()->getPlayer($player), $money === -1 ? $this->getLoader()->getConfig()->get("Money-Per-Player-Assist") * $amount : $money * $amount);
		}
		return $this->setPlayerAssists($player, $this->getPlayerAssists($player) + $amount);
	}

	/**
	 * @param     $player
	 * @param int $amount
	 * @param int $points
	 * @param int $money
	 *
	 * @return bool
	 */
	public function addEntityKills($player, int $amount = 1, int $points = -1, int $money = -1): bool {
		$this->addPoints($player, $points === -1 ? $this->getLoader()->getConfig()->get("Points-Per-Entity-Kill") * $amount : $points * $amount);
		if($this->getLoader()->isEconomyEnabled()) {
			$this->getLoader()->getEconomy()->addMoney($this->getLoader()->getServer()->getPlayer($player), $money === -1 ? $this->getLoader()->getConfig()->get("Money-Per-Entity-Kill") * $amount : $money * $amount);
		}
		return $this->setEntityKills($player, $this->getEntityKills($player) + $amount);
	}

	/**
	 * @param     $player
	 * @param int $amount
	 * @param int $subtractPoints
	 *
	 * @return bool
	 */
	public function addDeaths($player, int $amount = 1, int $subtractPoints = -1, $subtractMoney = -1): bool {
		$this->addPoints($player, $subtractPoints === -1 ? -$this->getLoader()->getConfig()->get("Points-Lost-Per-Death") * $amount : -$subtractPoints * $amount);
		if($this->getLoader()->isEconomyEnabled() && $this->getLoader()->getConfig()->get("Enable-Money-Leeching") === true) {
			$this->getLoader()->getEconomy()->takeMoney($this->getLoader()->getServer()->getPlayer($player), $subtractMoney === -1 ? $this->getLoader()->getConfig()->get("Money-Per-Player-Kill") * $amount : $subtractMoney * $amount);
		}
		return $this->setDeaths($player, $this->getDeaths($player) + $amount);
	}
}