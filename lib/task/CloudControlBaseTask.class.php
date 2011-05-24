<?php

/**
 * This is the base task of all tasks within the sfCloudControlPlugin.
 *
 * This task provides handling of the exit code returned to the worker API of cloudControl.
 */
abstract class CloudControlBaseTask extends InterruptableTask
{
  /**
   * The task has been completed successfully.
   *
   * @var int
   */
  const RETURN_CODE_NO_ERROR = 0;

  /**
   * The task encountered an error and shall be restarted.
   *
   * @var int
   */
  const RETURN_CODE_ERROR_RESTART = 1;

  /**
   * The task has encountered an error and shall shut down.
   *
   * @var int
   */
  const RETURN_CODE_ERROR = 2;

  /**
   * The return code that will actually be returned to the operating system.
   *
   * @var int
   */
  private $returnCode = self::RETURN_CODE_NO_ERROR;

  /**
   * Returns the list of all valid return codes mentioned above.
   *
   * @return array
   */
  private function getValidReturnCodes()
  {
    return array(
      self::RETURN_CODE_NO_ERROR,
      self::RETURN_CODE_ERROR_RESTART,
      self::RETURN_CODE_ERROR,
    );
  }

  /**
   * (non-PHPdoc)
   * @see sfTask::configure()
   */
  protected function configure()
  {
    parent::configure();

    $this->namespace = 'cloudcontrol';
  }

  /**
   * Checks whether the given code is a valid return code.
   *
   * @param int $code
   *
   * @return bool
   */
  final protected function isValidReturnCode($code)
  {
    return in_array($code, $this->getValidReturnCodes());
  }

  /**
   * Sets the return code that will be returned to the OS.
   *
   * @uses CloudControlBaseTask::isValidReturnCode()
   *
   * @throws InvalidArgumentException
   *
   * @param int $code
   *
   * @return CloudControlBaseTask $this
   */
  final protected function setReturnCode($code)
  {
    if ($this->isValidReturnCode($code))
    {
      $this->returnCode = $code;
    }
    else
    {
      throw InvalidArgumentException('The given return code does not exist.');
    }

    return $this;
  }

  /**
   * Returns the code set to be returned to the operating system.
   *
   * @return int
   */
  final protected function getReturnCode()
  {
    return $this->returnCode;
  }

  /**
   * Returns the shared temp directory on the cloudControl infrastructure.
   *
   * This directory is shared among every PHP process, even if unionMount is disabled.
   *
   * @return string
   */
  final public static function getSharedTempDirectory()
  {
    return realpath(getenv('TMPDIR'));
  }

  /**
   * Checks whether the current process is a worker.
   *
   * @return bool
   */
  final public static function isWorker()
  {
    $workerId = getenv('WRK_ID');

    return !empty($workerId);
  }

  /**
   * Returns the worker id of the current process.
   *
   * @throws RuntimeException If this process is not a worker.
   *
   * @return string The worker identifier.
   */
  final public static function getWorkerId()
  {
    if (!self::isWorker())
    {
      throw new RuntimeException('The current process is no cloudControl worker.');
    }
    else
    {
      return getenv('WRK_ID');
    }
  }
}