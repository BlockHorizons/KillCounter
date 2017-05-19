<?php

namespace BlockHorizons\KillCounter\listeners;

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

	private $kills = [];
	/** @var KillingSpree[] */
	private $killingSpree = [];

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

				if(!isset($this->kills[$lastPlayerAttacker->getName()])) {
					$this->kills[$lastPlayerAttacker->getName()] = 0;
				} elseif($this->kills[$lastPlayerAttacker->getName()] >= $this->getLoader()->getConfig()->get("Kills-For-Killing-Spree", 5)) {
					$this->startKillingSpree($lastPlayerAttacker);
				}
				if(isset($this->killingSpree[$lastPlayerAttacker->getName()])) {
					$this->killingSpree[$lastPlayerAttacker->getName()]->addKills();
					$extraPoints = $this->killingSpree[$lastPlayerAttacker->getName()]->getKills() * $this->getLoader()->getConfig()->get("Points-Added-Per-Spree-Kill");
				}
				$this->kills[$lastPlayerAttacker->getName()] += 1;

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

				if(!isset($this->kills[$killer->getName()])) {
					$this->kills[$killer->getName()] = 0;
				} elseif($this->kills[$killer->getName()] >= $this->getLoader()->getConfig()->get("Kills-For-Killing-Spree", 5)) {
					$this->startKillingSpree($killer);
				}
				if(isset($this->killingSpree[$killer->getName()])) {
					$this->killingSpree[$killer->getName()]->addKills();
					$extraPoints = $this->killingSpree[$killer->getName()]->getKills() * $this->getLoader()->getConfig()->get("Points-Added-Per-Spree-Kill");
				}
				$this->kills[$killer->getName()] += 1;

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
		$this->endKillingSpree($entity);
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
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function startKillingSpree(Player $player): bool {
		$this->killingSpree[$player->getName()] = new KillingSpree($this->getLoader(), $player);
		return true;
	}

	/**
	 * @param Player $player
	 *
	 * @return KillingSpree|null
	 */
	public function getKillingSpree(Player $player) {
		return $this->killingSpree[$player->getName()];
	}

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function endKillingSpree(Player $player): bool {
		if($this->isOnKillingSpree($player)) {
			$this->getLoader()->getServer()->broadcastMessage(TF::YELLOW . "The killing spree of " . TF::RED . $player->getName() . TF::YELLOW . " has ended, with a total of " . TF::RED . $this->killingSpree[$player->getName()]->getKills() . " kills!");
			unset($this->killingSpree[$player->getName()]);
			return true;
		}
		return false;
	}

	/**
	 * @param Player $player
	 *
	 * @return bool
	 */
	public function isOnKillingSpree(Player $player) {
		return isset($this->killingSpree[$player->getName()]);
	}
}