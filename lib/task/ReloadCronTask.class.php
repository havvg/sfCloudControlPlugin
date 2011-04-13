<?php

class ReloadCronTask extends CloudControlBaseTask
{
  /**
   * Restart this task if aborted.
   *
   * @see InterruptableTask::shutdown
   *
   * @return void
   */
  protected function doShutdown()
  {
    $this->setReturnCode(CloudControlBaseTask::RETURN_CODE_ERROR_RESTART);
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
      new sfCommandOption('connection', null, sfCommandOption::PARAMETER_REQUIRED, 'The connection name', 'propel'),
    ));

    parent::configure();

    $this->name = 'reload-cron';
    $this->briefDescription = 'Initiate reload cron process.';
    $this->detailedDescription = 'This task will create a reload file for the CronTask.';
  }

  /**
   * Returns the filename of the reload cron lock file.
   *
   * @param sfApplicationConfiguration $configuration
   *
   * @return string
   */
  public static function getReloadFilename(sfApplicationConfiguration $configuration)
  {
    return CloudControlBaseTask::getSharedTempDirectory() . DIRECTORY_SEPARATOR . $configuration->getApplication() . DIRECTORY_SEPARATOR . $configuration->getEnvironment() . DIRECTORY_SEPARATOR . 'reload_cron.lck';
  }

  /**
   * Sends the interrupt signal for reloading crontab to the cron task, if any.
   *
   * @return int
   */
  protected function execute($arguments = array(), $options = array())
  {
    try
    {
      $pid = CronTask::getPID($this->configuration);

      $filename = self::getReloadFilename($this->configuration);

      $this->getFilesystem()->mkdirs(dirname($filename));
      $this->getFilesystem()->touch($filename);

      return CloudControlBaseTask::RETURN_CODE_NO_ERROR;
    }
    catch (RuntimeException $e)
    {
      if ($e->getMessage() !== CronTask::EXCEPTION_NO_PROCESS)
      {
        throw new RuntimeException(sprintf('Caught RuntimeException in ReloadCronTask with message "%s"', $e->getMessage()), CloudControlBaseTask::RETURN_CODE_ERROR, $e);
      }
    }
  }
}