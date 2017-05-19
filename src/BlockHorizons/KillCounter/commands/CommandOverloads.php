<?php

namespace BlockHorizons\KillCounter\commands;

class CommandOverloads {

	private static $commandOverloads = [];

	/**
	 * @param string $killCounterCommand
	 *
	 * @return array
	 */
	public static function getOverloads(string $killCounterCommand): array {
		return self::$commandOverloads[$killCounterCommand];
	}

	public static function initialize() {
		self::$commandOverloads = [
			"killstats" => [
				0 => [
					"type" => "rawtext",
					"name" => "player",
					"optional" => true
				]
			]
		];
	}
}