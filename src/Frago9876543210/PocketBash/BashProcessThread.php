<?php

declare(strict_types=1);

namespace Frago9876543210\PocketBash;

use pocketmine\{snooze\SleeperNotifier, thread\Thread};
use Threaded;
use function fgets;
use function fwrite;
use function is_resource;
use function proc_close;
use function proc_open;
use function stream_set_blocking;
use const PTHREADS_INHERIT_NONE;

class BashProcessThread extends Thread{
	/** @var SleeperNotifier */
	private $notifier;
	/** @var resource */
	private $process;
	/** @var resource[] */
	private $pipes;
	/** @var bool */
	private $shutdown = false;

	/** @var Threaded */
	private $queue;
	/** @var string */
	private $buffer;

	public function __construct(SleeperNotifier $notifier){
		$this->notifier = $notifier;
		$this->queue = new Threaded();

		$this->start(PTHREADS_INHERIT_NONE);
	}

	public function onRun() : void{
		$descriptor = [
			["pipe", "r"],
			["pipe", "w"],
			["pipe", "w"]
		];
		$this->process = proc_open("/bin/bash", $descriptor, $pipes);
		if($this->process === false){
			return;
		}
		$this->pipes = $pipes;

		for($i = 1; $i <= 2; ++$i){
			stream_set_blocking($this->pipes[$i], false);
		}

		while(!$this->shutdown){
			$this->read();
		}
		proc_close($this->process);
	}

	public function getBuffer() : string{
		return $this->buffer;
	}

	public function write(string $command) : void{
		$this->queue[] = $command;
	}

	private function read() : void{
		if(($command = $this->queue->shift()) !== null){
			fwrite($this->pipes[0], "$command\n");
		}
		for($i = 1; $i <= 2; ++$i){
			while(is_resource($this->pipes[$i]) && ($buffer = fgets($this->pipes[$i])) !== false){
				$this->buffer = $buffer;
				$this->synchronized(function(){
					$this->notifier->wakeupSleeper();
					$this->wait();
				});
			}
		}
	}

	public function shutdown() : void{
		$this->shutdown = true;
	}
}