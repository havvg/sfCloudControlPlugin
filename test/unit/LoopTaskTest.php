<?php

require_once dirname(__FILE__) . '/../bootstrap/unit.php';

class LoopTaskTest extends sfPHPUnitBaseCloudControlPluginTestCase
{
  public static function setUpBeforeClass()
  {
    require_once(self::getPluginFixturesDir() . '/TestLoopTask.class.php');

    parent::setUpBeforeClass();
  }

  /**
   * @covers LoopTask::setLoop
   * @covers LoopTask::getLoop
   */
  public function testLoop()
  {
    $task = new TestLoopTask(new sfEventDispatcher(), new sfFormatter(80));

    // default
    $this->assertTrue($task->testGetLoop());

    $task->testSetLoop(false);
    $this->assertFalse($task->testGetLoop());

    $task->testSetLoop(true);
    $this->assertTrue($task->testGetLoop());

    $this->setExpectedException('InvalidArgumentException');
    $task->testSetLoop('any thing else but boolean');
  }
}