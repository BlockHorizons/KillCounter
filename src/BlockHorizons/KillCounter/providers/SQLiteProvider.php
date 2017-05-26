<?php

namespace BlockHorizons\KillCounter\providers;

use BlockHorizons\KillCounter\events\player\PlayerPointsChangeEvent;
use BlockHorizons\KillCounter\events\player\PointsChangeEvent;
use pocketmine\Player;

class SQLiteProvider extends BaseProvider {

	/** @var \SQLite3 $database */
	private $database;

	/**
	 * @return bool
	 */
	public function initializeDatabase(): bool {
		if(!file_exists($file = $this->getLoader()->getDataFolder() . "killcounter.sqlite3")) {
			file_put_contents($file, "");
		}
		$this->database = new \SQLite3($file);
		$query = "CREATE TABLE IF NOT EXISTS KillCounter(
					  Player VARCHAR(16) PRIMARY KEY,
					  PlayerKills INT,
					  PlayerAssists INT,
					  EntityKills INT,
					  Deaths INT,
					  Points INT)";
		$this->database->exec($query);

		$query = "CREATE TABLE IF NOT EXISTS Achievements(
					  Player VARCHAR(16),
					  Achievement VARCHAR(128),
					  PRIMARY KEY(Player, Achievement))";
		return $this->database->exec($query);
	}

	/**
	 * @param        $player
	 * @param string $achievement
	 *
	 * @return bool
	 */
	public function achieveAchievement($player, string $achievement): bool {
		$player = $this->turnToPlayerName($player);

		$query = "INSERT INTO Achievements(Player, Achievement) VALUES ('" . $this->escape($player) . "', '" . $this->escape($achievement) . "')";
		return $this->database->exec($query);
	}

	/**
	 * @param        $player
	 * @param string $achievement
	 *
	 * @return bool
	 */
	public function hasAchievement($player, string $achievement): bool {
		$player = $this->turnToPlayerName($player);

		$query = "SELECT * FROM Achievements WHERE Player = '" . $this->escape($player) . "' AND Achievement = '" . $this->escape($achievement) . "'";
		return !empty($this->database->query($query)->fetchArray(SQLITE3_ASSOC));
	}

	/**
	 * @param Player|string $player
	 *
	 * @return bool
	 */
	public function initializePlayer($player): bool {
		$player = $this->turnToPlayerName($player);

		$query = "INSERT INTO KillCounter(Player, PlayerKills, PlayerAssists, EntityKills, Points, Deaths) VALUES ('" . $this->escape($player) . "', 0, 0, 0, 0, 0)";
		return $this->database->exec($query);
	}

	/**
	 * @param Player|string $player
	 *
	 * @return array
	 */
	public function getPlayerStats($player): array {
		$player = $this->turnToPlayerName($player);

		$query = "SELECT * FROM KillCounter WHERE Player = '" . $this->escape($player) . "'";
		return $this->database->query($query)->fetchArray(SQLITE3_ASSOC);
	}

	/**
	 * @param     $player
	 * @param int $amount
	 *
	 * @return bool
	 */
	public function setPlayerKills($player, int $amount): bool {
		$player = $this->turnToPlayerName($player);

		$query = "UPDATE KillCounter SET PlayerKills = $amount WHERE Player = '" . $this->escape($player) . "'";
		return $this->database->exec($query);
	}

	/**
	 * @param     $player
	 * @param int $amount
	 *
	 * @return bool
	 */
	public function setPlayerAssists($player, int $amount): bool {
		$player = $this->turnToPlayerName($player);

		$query = "UPDATE KillCounter SET PlayerAssists = $amount WHERE Player = '" . $this->escape($player) . "'";
		return $this->database->exec($query);
	}

	/**
	 * @param     $player
	 * @param int $amount
	 *
	 * @return bool
	 */
	public function setPoints($player, int $amount): bool {
		$player = $this->turnToPlayerName($player);

		if(($foundPlayer = $this->getLoader()->getServer()->getPlayer($player)) !== null) {
			$this->getLoader()->getServer()->getPluginManager()->callEvent($ev = new PlayerPointsChangeEvent($this->getLoader(), $foundPlayer, $this->getPoints($foundPlayer), $amount));
			$amount = $ev->getNext();
			if($ev->isCancelled()) {
				return false;
			}
		}

		$query = "UPDATE KillCounter SET Points = $amount WHERE Player = '" . $this->escape($player) . "'";
		return $this->database->exec($query);
	}

	/**
	 * @param     $player
	 * @param int $amount
	 *
	 * @return bool
	 */
	public function setEntityKills($player, int $amount): bool {
		$player = $this->turnToPlayerName($player);

		$query = "UPDATE KillCounter SET EntityKills = $amount WHERE Player = '" . $this->escape($player) . "'";
		return $this->database->exec($query);
	}

	/**
	 * @param     $player
	 * @param int $amount
	 *
	 * @return bool
	 */
	public function setDeaths($player, int $amount): bool {
		$player = $this->turnToPlayerName($player);

		$query = "UPDATE KillCounter SET Deaths = $amount WHERE Player = '" . $this->escape($player) . "'";
		return $this->database->exec($query);
	}

	/**
	 * @return bool
	 */
	public function closeDatabase(): bool {
		if($this->database instanceof \SQLite3) {
			$this->database->close();
			return true;
		}
		return false;
	}

	/**
	 * @param $player
	 *
	 * @return bool
	 */
	public function playerExists($player): bool {
		$player = $this->turnToPlayerName($player);

		$query = "SELECT * FROM KillCounter WHERE Player = '" . $this->escape($player) . "'";
		return !empty($this->database->query($query)->fetchArray(SQLITE3_ASSOC));
	}

	/**
	 * @param int $limit
	 *
	 * @return array
	 */
	public function getPointsTop(int $limit = 10): array {
		$query = "SELECT * FROM KillCounter ORDER BY Points DESC LIMIT $limit";
		$top = [];
		$return = $this->database->query($query);
		for($i = 0; $i < $limit; $i++) {
			$step = $return->fetchArray(SQLITE3_ASSOC);
			if(!empty($step["Player"])){
			       $top[$step["Player"]] = $step["Points"];
			}
		}
		return $top;
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	private function escape(string $string): string {
		return \SQLite3::escapeString($string);
	}
}
