<?php

class ReloadCronTask extends CloudControlBaseTask
{
  /**
   * (non-PHPdoc)
   * @see InterruptableTask::shutdown
   */
  protected function doShutdown() {}

  /**
   * Set up information about this task.
   *
   * @uses pcntl_signal
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
   * @return void
   */
  protected function execute($arguments = array(), $options = array())
  {
    try
    {
      $pid = CronTask::getPID($this->configuration);

      $filename = self::getReloadFilename($this->configuration);

      $this->getFilesystem()->mkdirs(dirname($filename));
      $this->getFilesystem()->touch($filename);
    }
    catch (RuntimeException $e)
    {
      if ($e->getMessage() !== CronTask::EXCEPTION_NO_PROCESS)
      {
        throw $e;
      }
    }
  }
}