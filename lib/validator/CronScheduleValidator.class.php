<?php

class CronScheduleValidator extends sfValidatorBase
{
  /**
   * Configures the current validator.
   *
   * @see sfValidatorBase
   *
   * @param array $options   An array of options
   * @param array $messages  An array of error messages
   *
   * @return void
   */
  protected function configure($options = array(), $messages = array())
  {
    $this->setOption('empty_value', null);
  }

  /**
   * Checks whether the given value is a cron schedule string.
   *
   * @uses CronParser
   *
   * @throws sfValidatorError
   *
   * @param string $value The value to be validated as a cron schedule string.
   *
   * @return string
   */
  protected function doClean($value)
  {
    try
    {
      $parser = new CronParser($value);
    }
    catch (InvalidArgumentException $e)
    {
      throw new sfValidatorError($this, 'invalid', array('value' => $value, 'invalid' => $this->getOption('invalid')));
    }

    return $value;
  }
}