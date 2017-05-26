<?php

namespace BlockHorizons\KillCounter\events\player;

use BlockHorizons\KillCounter\events\BaseEvent;
use BlockHorizons\KillCounter\listeners\PlayerEventListener;
use BlockHorizons\KillCounter\Loader;
use pocketmine\event\Cancellable;
use pocketmine\Player;

class PlayerPointsChangeEvent extends PlayerEvent implements Cancellable {

	public static $handlerList = null;

	private $previousAmount;
	private $nextAmount;

	/*
	 * Called when the points of an ONLINE player have been changed.
	 *
	 * This does NOT get called if the player is offline.
	 */
	public function __construct(Loader $loader, Player $player, int $previousAmount, int $nextAmount) {
		parent::__construct($loader, $player);
		$this->previousAmount = $previousAmount;
		$this->nextAmount = $nextAmount;
	}

	/**
	 * @return int
	 */
	public function getPrevious(): int {
		return $this->previousAmount;
	}

	/**
	 * @return int
	 */
	public function getNext(): int {
		return $this->nextAmount;
	}

	/**
	 * @param int $nextAmount
	 */
	public function setNext(int $nextAmount) {
		$this->nextAmount = $nextAmount;
	}
}