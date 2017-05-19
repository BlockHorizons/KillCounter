<?php

namespace BlockHorizons\KillCounter\commands;

use BlockHorizons\KillCounter\commands\CommandOverloads;
use BlockHorizons\KillCounter\Loader;
use BlockHorizons\KillCounter\providers\BaseProvider;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;

abstract class BaseCommand extends Command implements PluginIdentifiableCommand {

	private $loader;

	public function __construct(Loader $loader, $name, $description = "", $usageMessage = null, $aliases = []) {
		parent::__construct($name, $description, $usageMessage, $aliases);
		$this->setPermission("killcounter.command." . $this->getName());
		$this->loader = $loader;
	}

	/**
	 * @return Loader
	 */
	public function getPlugin(): Loader {
		return $this->loader;
	}

	/**
	 * @return Loader
	 */
	public function getLoader(): Loader {
		return $this->loader;
	}

	/**
	 * @return BaseProvider
	 */
	public function getProvider(): BaseProvider {
		return $this->getLoader()->getProvider();
	}

	public function generateCustomCommandData(Player $player) {
		$commandData = parent::generateCustomCommandData($player);
		$commandData["overloads"]["default"]["input"]["parameters"] = CommandOverloads::getOverloads($this->getName());
		return $commandData;
	}
}