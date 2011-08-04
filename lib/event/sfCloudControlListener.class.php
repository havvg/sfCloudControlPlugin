<?php

class sfCloudControlListener
{
  /**
   * Add the reload cron worker on the given event.
   *
   * The related object will be checked for changes on schedule or command line.
   *
   * @uses sfCloudControl::addWorker()
   *
   * @param sfEvent $event The event is required to provide an 'object' containing a Crontab reference.
   *
   * @return bool Whether the worker has been added.
   */
  public static function addReloadCronWorker(sfEvent $event)
  {
    if (!empty($event['object']) and ($event['object'] instanceof Crontab))
    {
      $cron =& $event['object'];

      $cronModified = false;
      if ($cron->isModified())
      {
        /*
         * The list of columns that will trigger a change in crontab.
         */
        $columns = array(
          CrontabPeer::SCHEDULE,
          CrontabPeer::COMMAND,
          CrontabPeer::PARAMETERS,
        );

        foreach ($columns as $eachColumn)
        {
          if ($cron->isColumnModified($eachColumn))
          {
            $cronModified = true;
            break;
          }
        }
      }

      if (!$cron->isNew() and !$cron->isDeleted() and !$cronModified)
      {
        return false;
      }

      try
      {
        $cloudControl = new sfCloudControl();
        return (bool) $cloudControl->addWorker('symfony', ReloadCronTask::getWorkerParamsString());
      }
      catch (RuntimeException $e)
      {
        if ($e->getMessage() == CronTask::EXCEPTION_NO_PROCESS)
        {
          /*
           * The worker has actually not been added.
           * However this is fine, as there is no use to it while there is no CronTask running.
           */
          return true;
        }
        else
        {
          throw $e;
        }
      }
    }

    return false;
  }
}