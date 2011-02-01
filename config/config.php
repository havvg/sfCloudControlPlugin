<?php

if (in_array('sfCCCrontab', sfConfig::get('sf_enabled_modules')))
{
  $this->dispatcher->connect('routing.load_configuration', array('sfCloudControlRouting', 'addRouteForAdminCrontab'));
}