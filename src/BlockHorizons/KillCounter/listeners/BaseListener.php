<?php

namespace BlockHorizons\KillCounter\listeners;

use BlockHorizons\KillCounter\Loader;
use BlockHorizons\KillCounter\providers\BaseProvider;
use pocketmine\event\Listener;

class BaseListener implements Listener {

	private $loader;

	public function __construct(Loader $loader) {
		$this->loader = $loader;
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
}