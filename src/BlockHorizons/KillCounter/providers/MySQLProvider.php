<?php

namespace BlockHorizons\KillCounter\providers;

use BlockHorizons\KillCounter\events\player\PlayerPointsChangeEvent;
use BlockHorizons\KillCounter\Loader;
use pocketmine\Player;

class MySQLProvider extends BaseProvider {

	/** @var \mysqli */
	private $database;

	public function __construct(Loader $loader) {
		parent::__construct($loader);
	}

	/**
	 * @return bool
	 */
	public function initializeDatabase(): bool {
		$cfg = $this->getLoader()->getConfig();
		$this->database = new \mysqli($cfg->get("Host"), $cfg->get("User"), $cfg->get("Password"), $cfg->get("Database"), $cfg->get("Port"));
		if($this->database->connect_error !== null) {
			throw new \mysqli_sql_exception("No connection could be made to the MySQL database: " . $this->database->connect_error);
		}
		$query = "CREATE TABLE IF NOT EXISTS KillCounter(
					  Player VARCHAR(16) PRIMARY KEY,
					  PlayerKills INT,
					  PlayerAssists INT,
					  EntityKills INT,
					  Deaths INT,
					  Points INT)";
		$this->database->query($query);

		$query = "CREATE TABLE IF NOT EXISTS Achievements(
					  Player VARCHAR(16),
					  Achievement VARCHAR(128),
					  PRIMARY KEY(Player, Achievement))";
		return $this->database->query($query);
	}

	/**
	 * @param Player|string $player
	 *
	 * @return bool
	 */
	public function initializePlayer($player): bool {
		$player = $this->turnToPlayerName($player);

		$query = "INSERT INTO KillCounter(Player, PlayerKills, PlayerAssists, EntityKills, Points, Deaths) VALUES ('" . $this->escape($player) . "', 0, 0, 0, 0, 0)";
		return $this->database->query($query);
	}

	/**
	 * @param Player|string $player
	 *
	 * @return array
	 */
	public function getPlayerStats($player): array {
		$player = $this->turnToPlayerName($player);

		$query = "SELECT * FROM KillCounter WHERE Player = '" . $this->escape($player) . "'";
		return $this->database->query($query)->fetch_assoc();
	}

	/**
	 * @return bool
	 */
	public function closeDatabase(): bool {
		if($this->database instanceof \mysqli) {
			$this->database->close();
			return true;
		}
		return false;
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
		return $this->database->query($query);
	}

	public function setPlayerAssists($player, int $amount): bool {
		$player = $this->turnToPlayerName($player);

		$query = "UPDATE KillCounter SET PlayerAssists = $amount WHERE Player = '" . $this->escape($player) . "'";
		return $this->database->query($query);
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
		return $this->database->query($query);
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
		return $this->database->query($query);
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
		return $this->database->query($query);
	}

	/**
	 * @param $player
	 *
	 * @return bool
	 */
	public function playerExists($player): bool {
		$player = $this->turnToPlayerName($player);

		$query = "SELECT * FROM KillCounter WHERE Player = '" . $this->escape($player) . "'";
		return !empty($this->database->query($query)->fetch_assoc());
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
			$step = $return->fetch_assoc();
			if(!empty($step["Player"])){
				$top[$step["Player"]] = $step["Points"];
			}
		}
		return $top;
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
		return $this->database->query($query);
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
		return !empty($this->database->query($query)->fetch_assoc());
	}

	/**
	 * @param string $string
	 *
	 * @return string
	 */
	private function escape(string $string): string {
		return $this->database->real_escape_string($string);
	}
}