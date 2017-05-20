<?php

namespace BlockHorizons\KillCounter\handlers;

use BlockHorizons\KillCounter\Loader;
use pocketmine\Player;

abstract class BaseHandler {

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
}