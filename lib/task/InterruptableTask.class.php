<?php

/**
 * This task provides methods to be interrupted by a registered signal handler.
 *
 * @uses pcntl_signal()
 */
abstract class InterruptableTask extends sfBaseTask
{
  /**
   * The interrupt signal sent by the operating system.
   *
   * @var int
   */
  const INTERRUPT = SIGINT;

  /**
   * The task is requested to shut down nicely.
   *
   * This method is called, whenever the interrupt is retrieved.
   * It should be used to close any kind of connections, log stuff and shutdown nicely.
   *
   * The API will force the process to shutdown after a few seconds sending SIGKILL.
   *
   * The command.post_command event will be dispatched after this method.
   *
   * An implementation of this method should consider to alter the returned exit code.
   */
  abstract protected function doShutdown();

  /**
   * Internal method to call the shutdown method and exit this script correctly.
   *
   * @param int $signal The retrieved signal.
   */
  final public function shutdown($signal)
  {
    $this->logSection($this->namespace, sprintf('Received signal "%d"', $signal));

    $this->doShutdown();

    $this->dispatcher->notify(new sfEvent($this, 'command.post_command'));

    $this->logSection($this->namespace, sprintf('Exiting after shutdown with exit code "%d".', $this->getReturnCode()));

    exit($this->getReturnCode());
  }

  /**
   * Set up the shutdown interrupt, before running the task.
   *
   * @see BaseTask::doRun
   *
   * @return int
   */
  public function runFromCLI(sfCommandManager $commandManager, $options = null)
  {
    declare(ticks = 1);
    pcntl_signal(self::INTERRUPT, array(&$this, 'shutdown'));

    return parent::runFromCLI($commandManager, $options);
  }
}