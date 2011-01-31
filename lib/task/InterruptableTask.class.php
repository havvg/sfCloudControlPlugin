<?php

/**
 * This task provides methods to be interrupted be registered a signal handler.
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
  abstract protected function shutdown();

  /**
   * Internal method to call the shutdown method and exit this script correctly.
   *
   * @param int $signal The retrieved signal.
   */
  final protected function doShutdown($signal)
  {
    $this->logSection($this->namespace, sprintf('Received signal "%d"', $signal));

    $this->shutdown();

    $this->dispatcher->notify(new sfEvent($this, 'command.post_command'));

    $this->logSection($this->namespace, sprintf('Exiting after shutdown with exit code "%d".', $this->getReturnCode()));

    exit($this->getReturnCode());
  }

  /**
   * Constructor.
   *
   * @param sfEventDispatcher $dispatcher
   * @param sfFormatter $formatter
   */
  public function __construct(sfEventDispatcher $dispatcher, sfFormatter $formatter)
  {
    declare(ticks = 1);
    pcntl_signal(self::INTERRUPT, array(&$this, 'doShutdown'));

    parent::__construct($dispatcher, $formatter);
  }
}