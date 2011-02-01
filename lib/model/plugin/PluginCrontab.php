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
   * @param DateTime $lastRun The time this cron has been executed the last time. If null, the saved DateTime will be used.
   *
   * @return bool
   */
  public function isDue(DateTime $lastRun = null)
  {
    if (is_null($lastRun))
    {
      $lastRun = $this->getLastRunAt(null);
    }

    return $this->getCronParser()->isDue($lastRun);
  }

  /**
   * Returns the attached CronParser.
   *
   * @return CronParser
   */
  protected function getCronParser()
  {
    return $this->cronParser;
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

  /**
   * If the data has been saved correctly, we re-attach the CronParser, if required.
   *
   * @see BaseCrontab::save()
   *
   * @return int
   */
  public function save(PropelPDO $con = null)
  {
    $reAttach = $this->isColumnModified(CrontabPeer::SCHEDULE);

    if ($result = parent::save($con) and $reAttach)
    {
      $this->attachCronParser();
    }

    return $result;
  }

  /**
   * After successfully hydrating the instance, we attach the CronParser.
   *
   * @see BaseCrontab::hydrate()
   *
   * @return int
   */
  public function hydrate($row, $startcol = 0, $rehydrate = false)
  {
    if ($result = parent::hydrate($row, $startcol, $rehydrate))
    {
      $this->attachCronParser();
    }

    return $result;
  }
}
