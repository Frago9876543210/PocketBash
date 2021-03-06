<?php

declare(strict_types=1);

namespace Frago9876543210\PocketBash;

use pocketmine\plugin\PluginBase;
use pocketmine\snooze\SleeperNotifier;
use pocketmine\command\CommandSender;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\event\Listener;
use pocketmine\event\server\CommandEvent;

class Main extends PluginBase implements Listener{
	/** @var BashProcessThread */
	private $thread;
	/** @var CommandSender */
	private $sender;

	protected function onEnable() : void{
		$sleeper = $this->getServer()->getTickSleeper();
		$notifier = new SleeperNotifier();

		$sleeper->addNotifier($notifier, function() : void{
			$this->sender->sendMessage($this->thread->getBuffer());
			$this->thread->synchronized(function(BashProcessThread $thread){
				$thread->notify();
			}, $this->thread);
		});

		$this->thread = new BashProcessThread($notifier);

		if($this->getConfig()->get("redirect-commands")){
			$this->getServer()->getPluginManager()->registerEvents($this, $this);
		}

		$this->getServer()->getCommandMap()->register($this->getName(), new BashCommand($this));
	}

	public function getThread() : BashProcessThread{
		return $this->thread;
	}

	public function setSender(CommandSender $sender) : void{
		$this->sender = $sender;
	}

	public function onServerCommand(CommandEvent $event) : void{
		$sender = $event->getSender();
		$command = $event->getCommand();

		if($sender instanceof ConsoleCommandSender && $command !== "stop"){
			$this->sender = $sender;
			$this->thread->write($command);
			$event->cancel();
		}
	}

	protected function onDisable() : void{
		$this->thread->shutdown();
	}
}
