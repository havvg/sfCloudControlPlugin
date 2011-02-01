<?php

class sfCloudControlRouting
{
  /**
   * Prepends the route for the sfCCCrontab admin module.
   *
   * @param sfEvent $event
   *
   * @return void
   */
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
