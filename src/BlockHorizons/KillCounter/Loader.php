<?php

namespace BlockHorizons\KillCounter;

use BlockHorizons\KillCounter\commands\CommandOverloads;
use BlockHorizons\KillCounter\commands\KillStatsCommand;
use BlockHorizons\KillCounter\listeners\PlayerEventListener;
use BlockHorizons\KillCounter\providers\BaseProvider;
use BlockHorizons\KillCounter\providers\SQLiteProvider;
use economizer\Economizer;
use economizer\Transistor;
use EssentialsPE\EventHandlers\PlayerEventHandler;
use pocketmine\plugin\Plugin;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat as TF;

class Loader extends PluginBase {

	private $provider;
	private $economizer;
	private $economyEnabled = false;
	private $eventListener;

	public function onLoad() {
		CommandOverloads::initialize();
	}

	public function onEnable() {
		if(!is_dir($this->getDataFolder())) {
			mkdir($this->getDataFolder());
		}
		$this->prepareEconomy();
		$this->selectProvider();
		$this->getServer()->getPluginManager()->registerEvents($this->eventListener = new PlayerEventListener($this), $this);

		$this->saveDefaultConfig();
		$this->registerCommands();
	}

	public function onDisable() {
		$this->getProvider()->closeDatabase();
	}

	/**
	 * @return bool
	 */
	public function registerCommands(): bool {
		$commands = [
			new KillStatsCommand($this)
		];
		foreach($commands as $command) {
			$this->getServer()->getCommandMap()->register($command->getName(), $command);
		}
		return true;
	}

	/**
	 * @return BaseProvider
	 */
	public function getProvider(): BaseProvider {
		return $this->provider;
	}

	/**
	 * @return BaseProvider
	 */
	public function selectProvider(): BaseProvider {
		switch(strtolower($this->getConfig()->get("Provider"))) {
			default:
			case "sqlite":
			case "sqlite3":
				$this->provider = new SQLiteProvider($this);
				break;
		}
		return $this->provider;
	}

	public function prepareEconomy(): bool {
		if($this->getConfig()->get("Economy-Support") === true) {

			$pluginManager = $this->getServer()->getPluginManager();
			/** @var Plugin $economyPlugin */
			$economyPlugin = null;

			$economyPlugins = [
				$pluginManager->getPlugin("EconomyAPI"),
				$pluginManager->getPlugin("MassiveEconomy"),
				$pluginManager->getPlugin("PocketMoney"),
				$pluginManager->getPlugin("EssentialsPE")
			];
			foreach($economyPlugins as $ecoPlugin) {
				if($ecoPlugin !== null) {
					$economyPlugin = $ecoPlugin;
					break;
				}
			}
			if($economyPlugin === null || ($transistor = Economizer::getTransistorFor($economyPlugin)) === null) {
				$this->getLogger()->info(TF::RED . "[Error] No supported economy plugin could be found. Disabling economy support.");
				return false;
			}
			$this->economizer = new Economizer($this, $transistor);
			if($this->economizer->ready()) {
				$this->getLogger()->info(TF::AQUA . "Economy support enabled, using economy API from: " . $economyPlugin->getName());
				$this->economyEnabled = true;
				$this->economizer = $this->economizer->getTransistor();
			} else {
				$this->getLogger()->info(TF::RED . "Oops! Something went wrong when preparing the economy support.");
			}
			return true;
		}
		return false;
	}

	/**
	 * @return bool
	 */
	public function isEconomyEnabled(): bool {
		return $this->economyEnabled;
	}

	/**
	 * @return Transistor
	 */
	public function getEconomy(): Transistor {
		return $this->economizer;
	}

	/**
	 * @return PlayerEventListener
	 */
	public function getEventListener(): PlayerEventListener {
		return $this->eventListener;
	}
}