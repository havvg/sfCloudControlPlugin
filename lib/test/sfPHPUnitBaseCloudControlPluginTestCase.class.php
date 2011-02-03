<?php

/**
 * The sfCloudControlPlugin base class for PHPUnit tests.
 *
 * @see sfPHPUnit2Plugin
 */
class sfPHPUnitBaseCloudControlPluginTestCase extends sfPHPUnitBaseTestCase
{
  /**
   * Returns the path to the fixtures directory.
   *
   * @return string
   */
  protected static function getPluginFixturesDir()
  {
    return sfConfig::get('sf_plugins_dir') . DIRECTORY_SEPARATOR . 'sfCloudControlPlugin' . DIRECTORY_SEPARATOR . 'test' . DIRECTORY_SEPARATOR . 'fixtures';
  }
}