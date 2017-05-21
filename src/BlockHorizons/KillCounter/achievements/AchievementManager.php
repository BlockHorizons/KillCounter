<?php

namespace BlockHorizons\KillCounter\achievements;

use BlockHorizons\KillCounter\Loader;

class AchievementManager {

	private $loader;
	/** @var KillCounterAchievement[] */
	private $achievements = [];

	public function __construct(Loader $loader) {
		$this->loader = $loader;

		$data = $loader->getConfig()->get("Achievements");
		foreach($data as $name => $achievementData) {
			$processedData = @[
				"playerKills" => $achievementData["Player-Kills"] ?? 0,
				"playerAssists" => $achievementData["Player-Assists"] ?? 0,
				"entityKills" => $achievementData["Entity-Kills"] ?? 0,
				"deaths" => $achievementData["Deaths"] ?? 0,
				"pointsReward" => $achievementData["Points-Reward"] ?? 0,
				"message" => $achievementData["Message"] ?? ""
			];
			$this->achievements[strtolower($name)] = new KillCounterAchievement($this, strtolower($name), $processedData);
		}
	}

	/**
	 * @return Loader
	 */
	public function getLoader(): Loader {
		return $this->loader;
	}

	/**
	 * @param string $name
	 *
	 * @return KillCounterAchievement
	 */
	public function getAchievement(string $name): KillCounterAchievement {
		$name = strtolower($name);
		if(!isset($this->achievements[$name])) {
			return new KillCounterAchievement($this, $name);
		}
		return $this->achievements[$name];
	}

	/**
	 * @return KillCounterAchievement[]
	 */
	public function getAchievements(): array {
		return $this->achievements;
	}
}