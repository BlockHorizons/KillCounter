<?php

namespace BlockHorizons\KillCounter\events\player\killingspree;

use BlockHorizons\KillCounter\events\player\PlayerEvent;
use BlockHorizons\KillCounter\Loader;
use pocketmine\event\Cancellable;
use pocketmine\Player;

class PlayerKillingSpreeStartEvent extends PlayerEvent implements Cancellable {

	public static $handlerList = null;

	private $totalKills;
	private $startingKills;

	public function __construct(Loader $loader, Player $player, int $totalKills, int $startingKills) {
		parent::__construct($loader, $player);
		$this->totalKills = $totalKills;
		$this->startingKills = $startingKills;
	}

	/**
	 * Returns the total amount of kills from the kills without killing spree and with killing spree.
	 *
	 * @return int
	 */
	public function getTotalKills(): int {
		return $this->totalKills;
	}

	/**
	 * Gets the kills the killing spree will be started with. This number is usually zero.
	 *
	 * @return int
	 */
	public function getStartingKills(): int {
		return $this->startingKills;
	}

	/**
	 * Sets the kills the killing spree will be started with.
	 *
	 * @param int $amount
	 */
	public function setStartingKills(int $amount) {
		$originalKills = $this->totalKills - $this->startingKills;
		$this->startingKills = $amount;
		$this->totalKills = $amount + $originalKills;
	}
}