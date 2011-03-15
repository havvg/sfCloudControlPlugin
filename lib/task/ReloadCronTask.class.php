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
    $this->briefDescription = 'Issues an interrupt signal to reload cron process.';
    $this->detailedDescription = 'This task will send the RELOAD_INTERRUPT signal to the current CronTask.';
  }

  /**
   * Sends the interrupt signal for reloading crontab to the cron task, if any.
   *
   * @return void
   */
  protected function execute($arguments = array(), $options = array())
  {
    $context = sfContext::createInstance($this->configuration);

    try
    {
      $pid = CronTask::getPID($context->getConfiguration());

      $this->logSection($this->namespace, sprintf('Sending Interrupt "%d" to process with PID "%d".', CronTask::RELOAD_INTERRUPT, $pid));

      posix_kill($pid, CronTask::RELOAD_INTERRUPT);
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