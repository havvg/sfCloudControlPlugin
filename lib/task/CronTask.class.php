<?php

/**
 * This task checks whether a new worker needs to be started.
 *
 * @uses posix_getpid()
 * @uses pcntl_signal()
 */
class CronTask extends LoopTask
{
  const EXCEPTION_NO_PROCESS = 'There is no cron process running.';

  const EXCEPTION_PROCESS_ALREADY_RUNNING = 'There already is a cron running.';

  /**
   * The interrupt defined to reload schedule.
   *
   * @var int
   */
  const RELOAD_INTERRUPT = SIGUSR2;

  /**
   * A reference to the database manager.
   *
   * @var sfDatabaseManager
   */
  private $databaseManager;

  /**
   * The schedule for the cron.
   *
   * @var array of Crontab
   */
  private $schedule;

  /**
   * A reference to the cloudControl wrapper.
   *
   * @var sfCloudControl
   */
  private $cloudControl;

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

    $this->name = 'cron';
    $this->briefDescription = 'Runs the cron table.';
    $this->detailedDescription = 'This task is used to check whether there is a task scheduled and will call the worker API of cloudControl to execute the given task.';

    pcntl_signal(self::RELOAD_INTERRUPT, array(&$this, 'reload'));

    // Please restart this task, if it ends.
    $this->setReturnCode(CloudControlBaseTask::RETURN_CODE_ERROR_RESTART);
  }

  /**
   * This method is called whenever the RELOAD_INTERRUPT is received.
   *
   * @param int $signal
   *
   * @return void
   */
  public function reload($signal)
  {
    $this->logSection($this->namespace, sprintf('Received signal "%d".', $signal));
    $this->reloadSchedule();
  }

  /**
   * Reloads the schedule of the crontab.
   *
   * @return CronTask $this
   */
  protected function reloadSchedule()
  {
    $this->logSection($this->namespace, 'Reloading crontab.');

    CrontabPeer::clearInstancePool();

    $this->schedule = CrontabPeer::retrieveAll();

    return $this;
  }

  /**
   * Creates a database manager for the current configuration.
   *
   * @param array $options Options passed to the sfDatabaseManager.
   *
   * @return CronTask $this
   */
  protected function createDatabaseManager(array $options = array())
  {
    $this->databaseManager = new sfDatabaseManager($this->configuration, $options);

    return $this;
  }

  /**
   * Returns the created database manager.
   *
   * @return sfDatabaseManager
   */
  protected function getDatabaseManager()
  {
    return $this->databaseManager;
  }

  /**
   * Creates a cloudControl wrapper for further usage.
   *
   * @return $this
   */
  protected function createCloudControl()
  {
    $this->cloudControl = new sfCloudControl();

    return $this;
  }

  /**
   * Returns the cloudControl wrapper.
   *
   * @return sfCloudControl
   */
  protected function getCloudControl()
  {
    return $this->cloudControl;
  }

  /**
   * Returns a writable propel connection to the database of the Crontab.
   *
   * @return PDO
   */
  protected function getPropelConnection()
  {
    static $connection;

    if (empty($connection))
    {
      $connection = Propel::getConnection(CrontabPeer::DATABASE_NAME, Propel::CONNECTION_WRITE);
    }

    return $connection;
  }

  /**
   * The task is requested to shutdown.
   *
   * Shuts down the sfDatabaseManager and remove the PID file.
   *
   * @see InterruptableTask::doShutdown
   */
  protected function doShutdown()
  {
    $this->getDatabaseManager()->shutdown();
    $this->getFilesystem()->remove(self::getPIDFilename($this->configuration));
  }

  /**
   * Returns the filename for the PID file.
   *
   * @param sfApplicationConfiguration $configuration
   *
   * @return string
   */
  public static function getPIDFilename(sfApplicationConfiguration $configuration)
  {
    return realpath(getenv('TMPDIR')) . DIRECTORY_SEPARATOR . $configuration->getApplication() . DIRECTORY_SEPARATOR . $configuration->getEnvironment() . DIRECTORY_SEPARATOR . 'cloudcontrol_cron.pid';
  }

  /**
   * Returns the current PID of the cron process.
   *
   * @throws RuntimeException If there is no cron process running.
   *
   * @param sfApplicationConfiguration $configuration
   *
   * @return int
   */
  public static function getPID(sfApplicationConfiguration $configuration)
  {
    $filename = self::getPIDFilename($configuration);
    if (file_exists($filename) and $pid = file_get_contents($filename) and $pid = intval($pid))
    {
      return $pid;
    }
    else
    {
      throw new RuntimeException(self::EXCEPTION_NO_PROCESS);
    }
  }

  /**
   * Sets up the cron with the current configuration.
   *
   * * Creates database manager.
   * * Loads cron schedule.
   * * Creates a cloudControl wrapper.
   * * Sets the PID file.
   *
   * @uses posix_getpid
   *
   * @see LoopTask::preExecute
   *
   * @param array $arguments
   * @param array $options
   *
   * @return void
   */
  protected function preExecute($arguments = array(), $options = array())
  {
    try
    {
      self::getPID($this->configuration);

      throw new RuntimeException(self::EXCEPTION_PROCESS_ALREADY_RUNNING);
    }
    catch (RuntimeException $e)
    {
      if ($e->getMessage() !== self::EXCEPTION_NO_PROCESS)
      {
        throw $e;
      }
    }

    $this
      ->createDatabaseManager()
      ->reloadSchedule()
      ->createCloudControl()
    ;

    $filename = self::getPIDFilename($this->configuration);

    $this->getFilesystem()->mkdirs(dirname($filename));
    $this->getFilesystem()->touch($filename);
    file_put_contents($filename, posix_getpid());
  }

  /**
   * The actual cron.
   *
   * @todo Add a wait time while running through the schedule in order to reduce system load.
   */
  protected function execute($arguments = array(), $options = array())
  {
    if (empty($this->schedule))
    {
      /**
       * A cron is scheduled at a minimum of one minute.
       * If there is no entry in the crontab right now, we can wait for this one minute, reducing system load.
       *
       * If the RELOAD_INTERRUPT is initiated the schedule will be reloaded and the cron will run again.
       *
       * After this one minute, we will see, whether the interrupt has been initiated, if not, we wait another minute .. and so on.
       */
      sleep(60);
    }
    else
    {
      foreach ($this->schedule as $eachCron)
      {
        /* @var $eachCron Crontab */
        if ($eachCron->isDue())
        {
          $this->logSection($this->namespace, sprintf('Running command line: %s %s', $eachCron->getCommand(), $eachCron->getParameters()), null, 'INFO');
          $this->getCloudControl()->addWorker($eachCron->getCommand(), $eachCron->getParameters());

          $eachCron
            ->setLastRunAt(new DateTime())
            ->save($this->getPropelConnection())
          ;
        }
      }

      sleep(60);
    }
  }
}