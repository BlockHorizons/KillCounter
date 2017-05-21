<?php

namespace BlockHorizons\KillCounter\events\player;

use BlockHorizons\KillCounter\events\BaseEvent;
use BlockHorizons\KillCounter\Loader;
use pocketmine\Player;

class PlayerEvent extends BaseEvent {

	private $player;

	public function __construct(Loader $loader, Player $player) {
		parent::__construct($loader);
		$this->player = $player;
	}

	/**
	 * @return Player
	 */
	public function getPlayer(): Player {
		return $this->player;
	}
}