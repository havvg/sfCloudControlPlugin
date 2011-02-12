<?php

/**
 * Migrations between versions 000 and 001.
 */
class sfCloudControlMigration001 extends sfCloudControlBaseMigration
{
  /**
   * Migrate up to version 001.
   */
  public function up()
  {
    $this->loadSql('001_Up.sql');
  }

  /**
   * Migrate down to version 000.
   */
  public function down()
  {
    $this->loadSql('001_Down.sql');
  }
}
