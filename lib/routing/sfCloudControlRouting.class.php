<?php

class sfCloudControlRouting
{
  public static function addRouteForAdminCrontab(sfEvent $event)
  {
    $event->getSubject()->prependRoute('sf_cccrontab', new sfPropelRouteCollection(array(
      'name'                 => 'sf_cccrontab',
      'model'                => 'Crontab',
      'module'               => 'sfCCCrontab',
      'prefix_path'          => 'sf_cccrontab',
      'with_wildcard_routes' => true,
      'column'               => 'id',
      'requirements'         => array(),
    )));
  }
}
