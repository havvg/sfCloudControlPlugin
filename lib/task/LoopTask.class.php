<?php

/**
 * This task is running infinitely.
 *
 * This class adds methods to interact with the looping: preExecute, postExecute, loop.
 */
abstract class LoopTask extends CloudControlBaseTask
{
  /**
   * A method called once before the execution of the looping task.
   *
   * This method should be used to configure database connections and other environment dependent settings.
   *
   * @param array $arguments
   * @param array $options
   *
   * @return void
   */
  protected function preExecute($arguments = array(), $options = array()) {}

  /**
   * A method called once after completing the looping task.
   *
   * This method will NOT be called, if the task is interrupted!
   *
   * @return void
   */
  protected function postExecute() {}

  /**
   * This method is called each time before continue the loop.
   *
   * The loop will cancel, if this method returns false.
   *
   * @return bool
   */
  protected function loop()
  {
    return true;
  }

  /**
   * Processing the defined task.
   *
   * @uses LoopTask::preExecute() Before running the infinite loop, this method is called once.
   * @uses LoopTask::loop() This method is called every time the loop is about to run and will cancel the loop, if false is returned.
   * @uses LoopTask::postExecute() This method is called once after the loop has been completed.
   *
   * @see sfBaseTask::doRun()
   *
   * @param sfCommandManager $commandManager
   * @param mixed $options
   *
   * @return int The exit code returned to the operating system.
   */
  protected function doRun(sfCommandManager $commandManager, $options)
  {
    $event = $this->dispatcher->filter(new sfEvent($this, 'command.filter_options', array('command_manager' => $commandManager)), $options);
    $options = $event->getReturnValue();

    $this->process($commandManager, $options);

    $event = new sfEvent($this, 'command.pre_command', array('arguments' => $commandManager->getArgumentValues(), 'options' => $commandManager->getOptionValues()));
    $this->dispatcher->notifyUntil($event);
    if ($event->isProcessed())
    {
      return $event->getReturnValue();
    }

    $this->checkProjectExists();

    $requiresApplication = $commandManager->getArgumentSet()->hasArgument('application') || $commandManager->getOptionSet()->hasOption('application');
    if (null === $this->configuration || ($requiresApplication && !$this->configuration instanceof sfApplicationConfiguration))
    {
      $application = $commandManager->getArgumentSet()->hasArgument('application') ? $commandManager->getArgumentValue('application') : ($commandManager->getOptionSet()->hasOption('application') ? $commandManager->getOptionValue('application') : null);
      $env = $commandManager->getOptionSet()->hasOption('env') ? $commandManager->getOptionValue('env') : 'test';

      if (true === $application)
      {
        $application = $this->getFirstApplication();

        if ($commandManager->getOptionSet()->hasOption('application'))
        {
          $commandManager->setOption($commandManager->getOptionSet()->getOption('application'), $application);
        }
      }

      $this->configuration = $this->createConfiguration($application, $env);
    }

    if (null !== $this->commandApplication && !$this->commandApplication->withTrace())
    {
      sfConfig::set('sf_logging_enabled', false);
    }

    $this->preExecute($commandManager->getArgumentValues(), $commandManager->getOptionValues());

    while ($this->loop())
    {
      $this->execute($commandManager->getArgumentValues(), $commandManager->getOptionValues());
    }

    $this->postExecute();

    $this->dispatcher->notify(new sfEvent($this, 'command.post_command'));

    return $this->getReturnCode();
  }
}