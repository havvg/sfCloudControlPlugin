<?php

/**
 * This is wrapper for some phpcclib methods.
 *
 * @uses CCAPI
 */
class sfCloudControl
{
  /**
   * A reference to the current configuration.
   *
   * @var sfCloudControlConfiguration
   */
  protected $configuration;

  /**
   * A reference to the CCAPI of the phpcclib.
   *
   * @var CCAPI
   */
  protected $ccApi;

  /**
   * Creates a new wrapper for the cloudControl API.
   *
   * @param sfCloudControlConfiguration $configuration If null, the sfCloudControlConfiguration::factory() will be used.
   */
  public function __construct(sfCloudControlConfiguration $configuration = null)
  {
    if (is_null($configuration))
    {
      $configuration = sfCloudControlConfiguration::factory();
    }

    $this->configuration = $configuration;

    $this->initialize();
  }

  /**
   * Set the CCAPI.
   *
   * @param CCAPI $api
   *
   * @return sfCloudControl $this
   */
  protected function setApi(CCAPI $api)
  {
    $this->ccApi = $api;

    return $this;
  }

  /**
   * Initializes the cloudControl wrapper by creating the CCAPI based on the provided configuration.
   *
   * @return sfCloudControl $this
   */
  protected function initialize()
  {
    $this->setApi(new CCAPI());

    return $this->authenticate();
  }

  /**
   * Authenticate against the cloudControl API.
   *
   * This method may be used, in case you caught an UnauthorizedException.
   *
   * @return sfCloudControl $this
   */
  public function authenticate()
  {
    $this->getApi()->createAndSetToken($this->getConfiguration()->getUserEmail(), $this->getConfiguration()->getUserPassword());

    return $this;
  }

  /**
   * Returns the current configuration.
   *
   * @return sfCloudControlConfiguration
   */
  public function getConfiguration()
  {
    return $this->configuration;
  }

  /**
   * Returns the current used CCAPI object.
   *
   * @return CCAPI
   */
  public function getApi()
  {
    return $this->ccApi;
  }

  /**
   * Adds a worker with the given command line.
   *
   * @param string $command
   * @param string $parameters
   *
   * @return stdClass Information about the worker added.
   */
  public function addWorker($command, $parameters)
  {
    return $this->getApi()->addWorker($this->getConfiguration()->getApplicationName(), $this->getConfiguration()->getDeploymentName(), $command, $parameters);
  }

  /**
   * Removes a worker.
   *
   * @param string $workerId The id of the worker to be removed.
   *
   * @return sfCloudControl $this
   */
  public function removeWorker($workerId)
  {
    try
    {
      $this->getApi()->removeWorker($this->getConfiguration()->getApplicationName(), $this->getConfiguration()->getDeploymentName(), $workerId);
    }
    catch (GoneError $e)
    {
      /**
       * The worker to be removed is not there.
       * Maybe it finished between the time issued to remove it.
       *
       * We do not raise an error here, because the worker is not there (anymore), which is the expected result.
       */
    }

    return $this;
  }

  /**
   * Returns a list of workers currently running.
   *
   * @return array of stdClass
   */
  public function getWorkerList()
  {
    return $this->getApi()->getWorkerList($this->getConfiguration()->getApplicationName(), $this->getConfiguration()->getDeploymentName());
  }

  /**
   * Returns the details of a given worker.
   *
   * @param string $workerId The id of the worker to retrieve information for.
   *
   * @return stdClass
   */
  public function getWorkerDetails($workerId)
  {
    return $this->getApi()->getWorkerDetails($this->getConfiguration()->getApplicationName(), $this->getConfiguration()->getDeploymentName(), $workerId);
  }

  /**
   * Return a list of all workers running the given command line.
   *
   * @param string $command The command the worker is using.
   * @param string $params The params the worker is using.
   *
   * @return array of string The worker ids of the workers running this command.
   */
  public function getRunningWorkers($command, $params)
  {
    $workers = array();

    foreach ($this->getWorkerList() as $eachWorker)
    {
      $details = $this->getWorkerDetails($eachWorker->wrk_id);
      if ($details->command === $command and $details->params === $params)
      {
        $workers[] = $details->wrk_id;
      }
    }

    return $workers;
  }
}