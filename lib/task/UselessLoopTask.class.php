<?php

/**
 * This is an example task implementing the LoopTask.
 *
 * It will output an incrementing number, starting at zero, until the worker is removed.
 */
class UselessLoopTask extends LoopTask
{
  private $counter = 0;

  protected function configure()
  {
    parent::configure();

    $this->name = 'useless-loop';
    $this->briefDescription = 'Does some counting, very nice!';
    $this->detailedDescription = 'This task knows how to count - really, ask it!';
  }

  protected function shutdown()
  {
    $this->logSection($this->namespace, 'UselessLoopTask shutting down nicely.');

    $this->setReturnCode(self::RETURN_CODE_NO_ERROR);
  }

  protected function preExecute($arguments = array(), $options = array())
  {
    $this->counter = 0;
  }

  protected function execute($arguments = array(), $options = array())
  {
    $this->log('Current counter: ' . ++$this->counter);
    sleep(15);
  }
}