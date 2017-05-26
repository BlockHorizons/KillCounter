<?php

namespace BlockHorizons\KillCounter\events\player;

use BlockHorizons\KillCounter\achievements\KillCounterAchievement;
use BlockHorizons\KillCounter\Loader;
use pocketmine\event\Cancellable;
use pocketmine\Player;

class PlayerKillCounterAchievementGainEvent extends PlayerEvent implements Cancellable {

	public static $handlerList = null;

	private $achievement;

	public function __construct(Loader $loader, Player $player, KillCounterAchievement $achievement) {
		parent::__construct($loader, $player);
		$this->achievement = $achievement;
	}

	/**
	 * @return KillCounterAchievement
	 */
	public function getAchievement(): KillCounterAchievement {
		return $this->achievement;
	}
}