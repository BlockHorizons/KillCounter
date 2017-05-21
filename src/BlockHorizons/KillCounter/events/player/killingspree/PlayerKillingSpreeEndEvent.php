<?php

namespace BlockHorizons\KillCounter\events\player\killingspree;

use BlockHorizons\KillCounter\events\player\PlayerEvent;
use BlockHorizons\KillCounter\Loader;
use pocketmine\event\Cancellable;
use pocketmine\Player;

class PlayerKillingSpreeEndEvent extends PlayerEvent implements Cancellable {

	private $totalKills;
	private $spreeKills;

	public function __construct(Loader $loader, Player $player, int $totalKills, int $spreeKills) {
		parent::__construct($loader, $player);
		$this->totalKills = $totalKills;
		$this->spreeKills = $spreeKills;
	}

	/**
	 * Returns the total amount of kills achieved during this killing spree, and the way to it.
	 *
	 * @return int
	 */
	public function getTotalKills(): int {
		return $this->totalKills;
	}

	/**
	 * Returns only the kills that have been achieved while the killing spree was active.
	 *
	 * @return int
	 */
	public function getSpreeKills(): int {
		return $this->spreeKills;
	}
}