<?php

class PluginCrontabPeer extends BaseCrontabPeer
{
  /**
   * Retrieves all registered Crontab entries.
   *
   * @return array of Crontab
   */
  public static function retrieveAll()
  {
    return self::doSelect(new Criteria(self::DATABASE_NAME));
  }
}
