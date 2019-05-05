<?php

declare(strict_types=1);

namespace Frago9876543210\PocketBash;

use pocketmine\{command\ConsoleCommandSender, event\Listener, event\server\CommandEvent, plugin\PluginBase,
	snooze\SleeperNotifier};

class Main extends PluginBase implements Listener{
	/** @var BashProcessThread */
	private $thread;
	/** @var ConsoleCommandSender */
	private $sender;

	public function onEnable() : void{
		$sleeper = $this->getServer()->getTickSleeper();
		$notifier = new SleeperNotifier();
		$sleeper->addNotifier($notifier, function() : void{
			$this->sender->sendMessage($this->thread->getBuffer());
			$this->thread->synchronized(function(BashProcessThread $thread){
				$thread->notify();
			}, $this->thread);
		});

		$this->thread = new BashProcessThread($notifier);

		$this->getServer()->getPluginManager()->registerEvents($this, $this);
	}

	public function onServerCommand(CommandEvent $e) : void{
		$sender = $e->getSender();
		$command = $e->getCommand();
		if($sender instanceof ConsoleCommandSender && $command !== "stop"){
			$this->sender = $sender;
			$this->thread->write($command);
			$e->setCancelled();
		}
	}

	public function onDisable() : void{
		$this->thread->shutdown();
	}
}