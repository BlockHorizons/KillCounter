<?php

namespace BlockHorizons\KillCounter\commands;

use BlockHorizons\KillCounter\Loader;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;

class KillsTopCommand extends BaseCommand {

	public function __construct(Loader $loader) {
		parent::__construct($loader, "killstop", "Lists the top killers", "/killstop [limit]", ["killtop", "statstop", "topkills", "topstats", "kt", "pointstop", "toppoints"]);
	}

	public function execute(CommandSender $sender, $commandLabel, array $args): bool {
		if(!$this->testPermission($sender)) {
			return false;
		}
		$limit = 10;
		if(isset($args[0])) {
			if(!is_numeric($args[0])) {
				$sender->sendMessage(TF::RED . "[Error] The limit should be numeric.");
				return true;
			}
			$limit = $args[0];
			if($limit > 20) {
				$sender->sendMessage(TF::RED . "[Error] Exceeded maximum limit, setting limit to 20...");
				$limit = 20;
			}
		}
		$data = $this->getLoader()->getProvider()->getPointsTop($limit);

		$sender->sendMessage(TF::GREEN . "--- " . TF::YELLOW . "Kill Points Top" . TF::GREEN . " ---");
		$i = 1;
		foreach($data as $player => $statsData) {
			$i++;
			$sender->sendMessage(TF::GREEN . "[" . $i . "] " . TF::YELLOW . ucfirst($player) . " : " . TF::RED . TF::BOLD . $statsData["Points"]);
		}
		return true;
	}
}