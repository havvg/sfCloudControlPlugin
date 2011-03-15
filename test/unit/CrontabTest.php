<?php

require_once dirname(__FILE__) . '/../bootstrap/unit.php';

class CrontabTest extends sfPHPUnitBaseCloudControlPluginTestCase
{
  /**
   * @covers Crontab::isDue
   */
  public function testIsDue()
  {
    $cron = new Crontab();
    $cron
      ->setName('Created Test Entry')
      ->setCommand('symfony')
      ->setParameters('cache:clear --env=test')
      ->setSchedule('* * * * *')
      ->setLastRunAt(new DateTime('-10 minutes'))
      ->save()
    ;

    // Did not run this minute
    $this->assertTrue($cron->isDue());

    // Reload from database
    $pk = $cron->getId();
    CrontabPeer::clearInstancePool();
    $cron = CrontabPeer::retrieveByPK($pk);
    $this->assertNotEmpty($cron);

    // Check correct Entry retrieved
    $this->assertEquals($pk, $cron->getId());
    $this->assertTrue($cron->isDue());

    // Already ran this minute
    $cron
      ->setLastRunAt(new DateTime())
      ->save()
    ;
    $this->assertFalse($cron->isDue());

    $cron->delete();
  }
}