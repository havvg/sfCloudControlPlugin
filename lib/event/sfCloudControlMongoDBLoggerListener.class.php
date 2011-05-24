<?php

class sfCloudControlMongoDBLoggerListener
{
  /**
   * Adds the current worker id to the log entry.
   *
   * The key 'wrk_id' will be added containing the worker identifier.
   * If this is not a worker process, no changes will be made.
   *
   * @param sfEvent $event
   * @param array $logEntry
   *
   * @return array
   */
  public static function addWorkerId(sfEvent $event, array $logEntry)
  {
    if (CloudControlBaseTask::isWorker())
    {
      $logEntry['wrk_id'] = CloudControlBaseTask::getWorkerId();
    }

    return $logEntry;
  }
}