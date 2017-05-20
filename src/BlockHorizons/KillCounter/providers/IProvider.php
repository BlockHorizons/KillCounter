<?php

namespace BlockHorizons\KillCounter\providers;

use pocketmine\Player;

interface IProvider {

	/**
	 * @return bool
	 */
	public function initializeDatabase(): bool;

	/**
	 * @param Player|string $player
	 *
	 * @return bool
	 */
	public function initializePlayer($player): bool;

	/**
	 * @param Player|string $player
	 *
	 * @return array
	 */
	public function getPlayerStats($player): array;

	/**
	 * @return bool
	 */
	public function closeDatabase(): bool;

	/**
	 * @param     $player
	 * @param int $amount
	 *
	 * @return bool
	 */
	public function setPlayerKills($player, int $amount): bool;

	/**
	 * @param     $player
	 * @param int $amount
	 *
	 * @return bool
	 */
	public function setPlayerAssists($player, int $amount): bool;

	/**
	 * @param     $player
	 * @param int $amount
	 *
	 * @return bool
	 */
	public function setEntityKills($player, int $amount): bool;

	/**
	 * @param     $player
	 * @param int $amount
	 *
	 * @return bool
	 */
	public function setPoints($player, int $amount): bool;

	/**
	 * @param     $player
	 * @param int $amount
	 *
	 * @return bool
	 */
	public function setDeaths($player, int $amount): bool;

	/**
	 * @param $player
	 *
	 * @return bool
	 */
	public function playerExists($player): bool;

	/**
	 * @param $player
	 *
	 * @return int
	 */
	public function getPlayerKills($player): int;

	/**
	 * @param $player
	 *
	 * @return int
	 */
	public function getPlayerAssists($player): int;

	/**
	 * @param $player
	 *
	 * @return int
	 */
	public function getEntityKills($player): int;

	/**
	 * @param $player
	 *
	 * @return int
	 */
	public function getDeaths($player): int;

	/**
	 * @param $player
	 *
	 * @return int
	 */
	public function getPoints($player): int;

	/**
	 * @param     $player
	 * @param int $amount
	 *
	 * @return bool
	 */
	public function addPoints($player, int $amount): bool;

	/**
	 * @param     $player
	 * @param int $amount
	 * @param int $points
	 *
	 * @return bool
	 */
	public function addPlayerKills($player, int $amount = 1, int $points = -1): bool;

	/**
	 * @param     $player
	 * @param int $amount
	 * @param int $points
	 *
	 * @return bool
	 */
	public function addPlayerAssists($player, int $amount = 1, int $points = -1): bool;

	/**
	 * @param     $player
	 * @param int $amount
	 * @param int $points
	 *
	 * @return bool
	 */
	public function addEntityKills($player, int $amount = 1, int $points = -1): bool;

	/**
	 * @param     $player
	 * @param int $amount
	 * @param int $subtractPoints
	 *
	 * @return bool
	 */
	public function addDeaths($player, int $amount = 1, int $subtractPoints = -1): bool;

	/**
	 * @param int $limit
	 *
	 * @return array
	 */
	public function getPointsTop(int $limit = 10): array;
}