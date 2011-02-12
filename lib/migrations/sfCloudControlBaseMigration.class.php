<?php

abstract class sfCloudControlBaseMigration extends sfMigration
{
  /**
   * Adds plugin directory to the file being loaded.
   *
   * @see sfMigration::loadSql()
   *
   * @param string $file The name of the file to load.
   *
   * @return void
   */
  protected function loadSql($file)
  {
    $pluginMigrationsDirectory =
      sfConfig::get('sf_plugins_dir') . DIRECTORY_SEPARATOR .
        'sfCloudControlPlugin' . DIRECTORY_SEPARATOR .
          'data' . DIRECTORY_SEPARATOR .
            'migrations' . DIRECTORY_SEPARATOR
    ;

    parent::loadSql($pluginMigrationsDirectory . trim($file, DIRECTORY_SEPARATOR));
  }
}