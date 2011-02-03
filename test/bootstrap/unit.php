<?php

$_test_dir = realpath(dirname(__FILE__) . '/../..');
$_root_dir = $_test_dir . '/../..';

require_once $_root_dir . '/config/ProjectConfiguration.class.php';
$configuration = ProjectConfiguration::hasActive() ? ProjectConfiguration::getActive() : new ProjectConfiguration(realpath($_root_dir));

// autoloader for sfPHPUnit2Plugin libs
$autoload = sfSimpleAutoload::getInstance(sfConfig::get('sf_cache_dir').'/project_autoload.cache');
$autoload->loadConfiguration(sfFinder::type('file')->name('autoload.yml')->in(array(
  sfConfig::get('sf_symfony_lib_dir').'/config/config',
  sfConfig::get('sf_config_dir'),
  $_root_dir . '/plugins/sfPHPUnit2Plugin/lib/config',

  // add plugins autoload.yml
  $_root_dir . '/plugins/sfCloudControlPlugin/lib/config'
)));
$autoload->register();