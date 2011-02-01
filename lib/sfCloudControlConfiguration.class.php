<?php

class sfCloudControlConfiguration
{
  protected
    $application,
    $deployment,
    $userEmail,
    $userPassword;

  /**
   * Creates a sfCloudControlConfiguration based on the application configuration.
   *
   * @throws sfConfigurationException
   *
   * @return sfCloudControlConfiguration
   */
  public static function factory()
  {
    try
    {
      return new self(sfConfig::get('app_cloudcontrol_application', ''), sfConfig::get('app_cloudcontrol_deployment', ''), sfConfig::get('app_cloudcontrol_email', ''), sfConfig::get('app_cloudcontrol_password', ''));
    }
    catch (sfInitializationException $e)
    {
      throw new sfConfigurationException('The configuration for cloudControl is erroneous.', 1, $e);
    }
  }

  /**
   * Creates a new cloudControl configuration.
   *
   * @param string $application
   * @param string $deployment
   * @param string $userEmail
   * @param string $userPassword
   */
  public function __construct($application, $deployment, $userEmail, $userPassword)
  {
    if (empty($application))
    {
      throw new sfInitializationException('The application name is missing.');
    }

    if (empty($deployment))
    {
      throw new sfInitializationException('The deployment name is missing.');
    }

    if (empty($userEmail))
    {
      throw new sfInitializationException('The user email is missing.');
    }

    if (empty($userPassword))
    {
      throw new sfInitializationException('The user password is missing.');
    }

    $this->application = $application;
    $this->deployment = $deployment;
    $this->userEmail = $userEmail;
    $this->userPassword = $userPassword;
  }

  /**
   * Returns the configured application name.
   *
	 * @return string
   */
  public function getApplicationName()
  {
    return $this->application;
  }

  /**
   * Returns the configured deployment name.
   *
   * @return string
   */
  public function getDeploymentName()
  {
    return $this->deployment;
  }

  /**
   * Returns the configured user email.
   *
   * @return string
   */
  public function getUserEmail()
  {
    return $this->userEmail;
  }

  /**
   * Returns the configured user password.
   *
   * @return string
   */
  public function getUserPassword()
  {
    return $this->userPassword;
  }
}