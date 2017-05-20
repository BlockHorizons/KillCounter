<?php

namespace BlockHorizons\KillCounter\listeners;

use BlockHorizons\KillCounter\handlers\KillingSpreeHandler;
use BlockHorizons\KillCounter\KillingSpree;
use BlockHorizons\KillCounter\Loader;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityDeathEvent;
use pocketmine\event\entity\EntityRegainHealthEvent;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\Player;
use pocketmine\utils\TextFormat as TF;

class PlayerEventListener extends BaseListener {

	private $damagedBy = [];
	private $lastPlayerDamageCause = [];

	public function __construct(Loader $loader) {
		parent::__construct($loader);
	}

	/**
	 * @param PlayerJoinEvent $event
	 */
	public function onJoin(PlayerJoinEvent $event) {
		if(!$this->getProvider()->playerExists($event->getPlayer())) {
			$this->getProvider()->initializePlayer($event->getPlayer());
		}
	}

	/**
	 * @param EntityDamageEvent $event
	 */
	public function onPlayerDamage(EntityDamageEvent $event) {
		if(!$event instanceof EntityDamageByEntityEvent) {
			return;
		}
		$attacker = $event->getDamager();
		$target = $event->getEntity();
		if(!$attacker instanceof Player || !$target instanceof Player) {
			return;
		}

		$this->damagedBy[$target->getName()][] = $attacker->getName();
		$this->lastPlayerDamageCause[$target->getName()] = $attacker->getName();
	}

	/**
	 * @param EntityRegainHealthEvent $event
	 */
	public function onHealthRegenerate(EntityRegainHealthEvent $event) {
		$entity = $event->getEntity();
		if(!$entity instanceof Player) {
			return;
		}
		$totalHealth = ($health = $entity->getHealth() + $event->getAmount()) > 20 ? 20 : $health;
		if($totalHealth === 20) {
			unset($this->damagedBy[$entity->getName()]);
			unset($this->lastPlayerDamageCause[$entity->getName()]);
		}
	}

	/**
	 * @param EntityDeathEvent $event
	 */
	public function onDeath(EntityDeathEvent $event) {
		$entity = $event->getEntity();
		if(!in_array($entity->getLevel()->getName(), $this->getLoader()->getConfig()->get("Disabled-Worlds", []))) {
			return;
		}
		if(!$entity instanceof Player) {
			$cause = $entity->getLastDamageCause();
			if($cause instanceof EntityDamageByEntityEvent) {
				$killer = $cause->getDamager();
				if($killer instanceof Player && $killer->isOnline()) {
					$killer->sendMessage(TF::AQUA . "+" . $this->getLoader()->getConfig()->get("Points-Per-Entity-Kill") . " Points! " . TF::YELLOW . "You killed a creature!");
					$this->getProvider()->addEntityKills($killer);
				}
			}
			return;
		}
	}
	public function onPlayerDeath(PlayerDeathEvent $event) {
		$entity = $event->getPlayer();
		$extraPoints = 0;
		if(!in_array($entity->getLevel()->getName(), $this->getLoader()->getConfig()->get("Disabled-Worlds", []))) {
			return;
		}
		if(($cause = $entity->getLastDamageCause())->getCause() !== EntityDamageEvent::CAUSE_ENTITY_ATTACK) {
			$lastPlayerAttacker = $this->getLoader()->getServer()->getPlayer($this->getLastPlayerAttacker($entity));
			if($lastPlayerAttacker !== null) {
				$this->getProvider()->addPlayerKills($lastPlayerAttacker);

				$this->getKillingSpreeHandler()->addKills($lastPlayerAttacker);

				if($this->getKillingSpreeHandler()->hasKillingSpree($lastPlayerAttacker)) {
					$this->getKillingSpreeHandler()->getKillingSpree($lastPlayerAttacker)->addKills();
					$extraPoints = $this->getKillingSpreeHandler()->getKillingSpree($lastPlayerAttacker)->getKills() * $this->getLoader()->getConfig()->get("Points-Added-Per-Spree-Kill");
				}

				$lastPlayerAttacker->sendMessage(TF::AQUA . "+" . (string) ($this->getLoader()->getConfig()->get("Points-Per-Player-Kill") + $extraPoints) . " Points! " . TF::YELLOW . "You killed " . $entity->getName() . "!");
				foreach($this->damagedBy[$entity->getName()] as $playerName) {
					if($playerName === $lastPlayerAttacker->getName()) {
						continue;
					}
					if(($player = $this->getLoader()->getServer()->getPlayer($playerName)) !== null) {
						$player->sendMessage(TF::AQUA . "+" . $this->getLoader()->getConfig()->get("Points-Per-Player-Assist") . " Points! " . TF::YELLOW . "You have assisted in killing " . $entity->getName() . "!");
						$this->getProvider()->addPlayerAssists($player);
					}
				}
			}
		}

		elseif($cause instanceof EntityDamageByEntityEvent) {
			$killer = $cause->getDamager();
			if($killer instanceof Player) {
				$this->getProvider()->addPlayerKills($killer);

				$this->getKillingSpreeHandler()->addKills($killer);
				if($this->getKillingSpreeHandler()->hasKillingSpree($killer)) {
					$this->getKillingSpreeHandler()->getKillingSpree($killer)->addKills();
					$extraPoints = $this->getKillingSpreeHandler()->getKillingSpree($killer)->getKills() * $this->getLoader()->getConfig()->get("Points-Added-Per-Spree-Kill");
				}

				$killer->sendMessage(TF::AQUA . "+" . (string) ($this->getLoader()->getConfig()->get("Points-Per-Player-Kill") + $extraPoints) . " Points! " . TF::YELLOW . "You killed " . $entity->getName() . "!");
				foreach($this->damagedBy[$entity->getName()] as $playerName) {
					if($playerName === $killer->getName()) {
						continue;
					}
					if(($player = $this->getLoader()->getServer()->getPlayer($playerName)) !== null) {
						$player->sendMessage(TF::AQUA . "+" . $this->getLoader()->getConfig()->get("Points-Per-Player-Assist") . " Points! " . TF::YELLOW . "You have assisted in killing " . $entity->getName() . "!");
						$this->getProvider()->addPlayerAssists($player);
					}
				}
			}
		}
		$this->getProvider()->addDeaths($entity);
		$this->getKillingSpreeHandler()->endKillingSpree($entity);
	}

	/**
	 * @param Player $player
	 *
	 * @return string
	 */
	public function getLastPlayerAttacker(Player $player): string {
		return $this->lastPlayerDamageCause[$player->getName()] ?? "";
	}

	/**
	 * @return KillingSpreeHandler
	 */
	public function getKillingSpreeHandler(): KillingSpreeHandler {
		return $this->getLoader()->getKillingSpreeHandler();
	}
}