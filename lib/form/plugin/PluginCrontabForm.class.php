<?php

class PluginCrontabForm extends BaseCrontabForm
{
  public function configure()
  {
    $this->setWidget('last_run_at', new sfWidgetFormDateTime(array('default' => date('Y-m-d H:i:s'))));

    $this->setValidator('schedule', new CronScheduleValidator(array(), array('invalid' => 'The given schedule is no valid cron schedule entry.')));
    $this->errorSchema = new sfValidatorErrorSchema($this->validatorSchema);
  }
}
