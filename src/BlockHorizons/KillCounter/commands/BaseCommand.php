<?php

namespace BlockHorizons\KillCounter\commands;

use BlockHorizons\KillCounter\commands\CommandOverloads;
use BlockHorizons\KillCounter\Loader;
use BlockHorizons\KillCounter\providers\BaseProvider;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\PluginIdentifiableCommand;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

abstract class BaseCommand extends Command implements PluginIdentifiableCommand {

	private $loader;

	public function __construct(Loader $loader, string $name, string $description = "", $usageMessage = null, array $aliases = []) {
		parent::__construct($name, $description, $usageMessage, $aliases);
		$this->setPermission("killcounter.command." . $this->getName());
		$this->loader = $loader;
	}

	/**
	 * @return Loader
	 */
	public function getPlugin(): plugin {
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

	public function generateCustomCommandData(Player $player): array {
		$commandData = parent::generateCustomCommandData($player);
		$commandData["overloads"]["default"]["input"]["parameters"] = CommandOverloads::getOverloads($this->getName());
		return $commandData;
	}
}
