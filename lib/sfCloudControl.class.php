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
}