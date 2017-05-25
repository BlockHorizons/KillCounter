<?php

namespace BlockHorizons\KillCounter\events;

use BlockHorizons\KillCounter\Loader;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\plugin\Plugin;

abstract class BaseEvent extends PluginEvent {

	public static $handlerList = null;
	private $loader;

	public function __construct(Loader $loader) {
		parent::__construct($loader);
	}

	/**
	 * @return Loader
	 */
	public function getLoader(): Loader {
		return $this->loader;
	}
}
