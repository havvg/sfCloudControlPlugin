<?php

class PluginCrontab extends BaseCrontab
{
  /**
   * A reference to the CronParser for this crontab entry.
   *
   * @var CronParser
   */
  protected $cronParser;

  /**
   * Returns a flag whether this entry should be executed.
   *
   * @param DateTime $lastRun The time this cron has been executed the last time.
   *
   * @return bool
   */
  public function isDue(DateTime $lastRun)
  {

  }

  /**
   * Attaches a CronParser to this instance and loads it with the saved schedule.
   *
   * If there is already a CronParser attached, it will be overwritten.
   *
   * @return PluginCrontab $this
   */
  protected function attachCronParser()
  {
    $this->cronParser = new CronParser($this->getSchedule());

    return $this;
  }
}
