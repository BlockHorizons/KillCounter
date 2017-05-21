<?php

namespace BlockHorizons\KillCounter\achievements;

use BlockHorizons\KillCounter\events\player\PlayerKillCounterAchievementGainEvent;
use pocketmine\Player;

class KillCounterAchievement {

	private $manager;
	private $name;

	private $playerKills;
	private $playerAssists;
	private $entityKills;
	private $deaths;
	private $pointsReward;
	private $message;

	public function __construct(AchievementManager $manager, string $name, array $data = []) {
		$this->manager = $manager;
		$this->name = $name;
		foreach($data as $key => $datum) {
			$this->{$key} = $datum;
		}
	}

	/**
	 * @return string
	 */
	public function getName(): string {
		return $this->name;
	}

	/**
	 * @return AchievementManager
	 */
	public function getManager(): AchievementManager {
		return $this->manager;
	}

	/**
	 * @return int
	 */
	public function getPlayerKills(): int {
		return $this->playerKills;
	}

	/**
	 * @return int
	 */
	public function getPlayerAssists(): int {
		return $this->playerAssists;
	}

	/**
	 * @return int
	 */
	public function getEntityKills(): int {
		return $this->entityKills;
	}

	/**
	 * @return int
	 */
	public function getDeaths(): int {
		return $this->deaths;
	}

	/**
	 * @return int
	 */
	public function getRewardPoints(): int {
		return $this->pointsReward;
	}

	/**
	 * @return string
	 */
	public function getMessage(): string {
		return $this->message;
	}

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function meetsRequirements(Player $player): bool {
		$provider = $this->getManager()->getLoader()->getProvider();
		if(!$provider->getPlayerKills($player) >= $this->playerKills) {
			return false;
		} elseif(!$provider->getPlayerAssists($player) >= $this->playerAssists) {
			return false;
		} elseif(!$provider->getEntityKills($player) >= $this->entityKills) {
			return false;
		} elseif(!$provider->getDeaths($player) >= $this->deaths) {
			return false;
		}
		return true;
	}

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function achieve(Player $player): bool {
		if(!$this->meetsRequirements($player)) {
			return false;
		}
		$this->getManager()->getLoader()->getServer()->getPluginManager()->callEvent($ev = new PlayerKillCounterAchievementGainEvent($this->getManager()->getLoader(), $player, $this));
		if($ev->isCancelled()) {
			return false;
		}
		$player->sendMessage($this->message);
		$this->getManager()->getLoader()->getProvider()->addPoints($player, $this->pointsReward);
		$this->getManager()->getLoader()->getProvider()->achieveAchievement($player, $this->getName());
		return true;
	}
}