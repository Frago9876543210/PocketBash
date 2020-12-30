<?php

declare(strict_types=1);

namespace Frago9876543210\PocketBash;

use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\command\utils\InvalidCommandSyntaxException;
use function count;
use function implode;

class BashCommand extends Command{
	/** @var Main */
	private $plugin;

	public function __construct(Main $plugin){
		parent::__construct("bash", "Allows to execute bash code in your server", "/bash <commands>");
		$this->plugin = $plugin;
	}

	public function execute(CommandSender $sender, string $commandLabel, array $args){
		if(count($args) === 0){
			throw new InvalidCommandSyntaxException();
		}

		$this->plugin->setSender($sender);
		$this->plugin->getThread()->write(implode(" ", $args));
	}
}
