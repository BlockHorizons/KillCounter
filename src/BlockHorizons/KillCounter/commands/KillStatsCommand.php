<?php

namespace BlockHorizons\KillCounter\commands;

use BlockHorizons\KillCounter\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;

class KillStatsCommand extends BaseCommand {

	public function __construct(Loader $loader) {
		parent::__construct($loader, "killstats", "Displays your current kill counter statistics", "/killstats [player]", ["ks", "stats", "kills"]);
	}

	/**
	 * @param CommandSender $sender
	 * @param string        $commandLabel
	 * @param array         $args
	 *
	 * @return bool
	 */
	public function execute(CommandSender $sender, $commandLabel, array $args): bool {
		if(!$this->testPermission($sender)) {
			return false;
		}
		if(!$sender instanceof Player && !isset($args[0])) {
			$sender->sendMessage(TF::RED . "[Error] You can't use this command through console.");
			return true;
		}
		$player = $sender;
		if(isset($args[0])) {
			if(($player = $this->getLoader()->getServer()->getPlayer($args[0])) === null) {
				$sender->sendMessage(TF::RED . "[Error] That player could not be found.");
				return true;
			}
		}
		$data = $this->getLoader()->getProvider()->getPlayerStats($player);

		$player->sendMessage(TF::GREEN . "--- " . TF::YELLOW . "KillCounter" . TF::GREEN . " ---");
		$player->sendMessage(
			TF::GREEN . "Player Kills: " . TF::YELLOW . $data["PlayerKills"] . "\n" .
			TF::GREEN . "Player Assists: " . TF::YELLOW . $data["PlayerAssists"] . "\n" .
			TF::GREEN . "Entity Kills: " . TF::YELLOW . $data["EntityKills"] . "\n" .
			TF::GREEN . "Deaths: " . TF::YELLOW . $data["Deaths"] . "\n" .
			TF::GREEN . "Points: " . TF::YELLOW . $data["Points"]);
		return true;
	}
}