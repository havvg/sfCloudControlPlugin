<?php

class ReloadCronTask extends LoopTask
{
  /**
   * We don't need to do anything.
   *
   * @see InterruptableTask::shutdown
   *
   * @return void
   */
  protected function doShutdown() { }

  /**
   * Return the params string to identify this worker.
   *
   * @return string
   */
  public static function getWorkerParamsString()
  {
    return 'cloudcontrol:reload-cron';
  }

  /**
   * Set up information about this task.
   *
   * @return void
   */
  protected function configure()
  {
    $this->addOptions(array(
      new sfCommandOption('application', null, sfCommandOption::PARAMETER_REQUIRED, 'The application name', 'backend'),
      new sfCommandOption('env', null, sfCommandOption::PARAMETER_REQUIRED, 'The environment', 'dev'),
    ));

    parent::configure();

    $this->name = 'reload-cron';
    $this->briefDescription = 'Initiate reload cron process.';
    $this->detailedDescription = 'This task will issue the CronTask to reload its schedule.';
  }

  /**
   * Ensure there is a Cron worker running, before looping.
   *
   * @param type $arguments
   * @param type $options
   */
  protected function preExecute($arguments = array(), $options = array())
  {
    $this->createCloudControl();

    $found = false;
    foreach ($this->getCloudControl()->getWorkerList() as $eachWorker)
    {
      $details = $this->getCloudControl()->getWorkerDetails($eachWorker->wrk_id);
      if (strpos($details->params, CronTask::getWorkerParamsString()) === 0)
      {
        $found = true;
        break;
      }
    }

    if (!$found)
    {
      throw new RuntimeException(CronTask::EXCEPTION_NO_PROCESS, CloudControlBaseTask::RETURN_CODE_ERROR);
    }
  }

  /**
   * The task actually does nothing but existing. The CronTask will check for it and remove it.
   *
   * @return int
   */
  protected function execute($arguments = array(), $options = array())
  {
    // Being lazy ..
  }
}