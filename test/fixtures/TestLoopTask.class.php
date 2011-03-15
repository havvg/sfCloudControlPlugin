<?php

class TestLoopTask extends LoopTask
{
  protected function configure()
  {
    parent::configure();

    $this->name = 'test-loop';
    $this->briefDescription = 'Does nothing.';
    $this->detailedDescription = 'Does nothing.';
  }

  protected function doShutdown() {}

  protected function execute($arguments = array(), $options = array()) {}

  public function testSetLoop($loop)
  {
    return $this->setLoop($loop);
  }

  public function testGetLoop()
  {
    return $this->getLoop();
  }
}