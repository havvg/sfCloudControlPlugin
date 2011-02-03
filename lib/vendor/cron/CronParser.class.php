<?php

/**
 * Cron schedule parser.
 *
 * @see http://stackoverflow.com/questions/321494/calculate-when-a-cron-job-will-be-executed-then-next-time/3453872#3453872
 * @see http://pastebin.com/S55pWzwt
 */
class CronParser
{
    /**
     * @var array Cron parts
     */
    private $_cronParts;

    /**
     * Constructor
     *
     * @param string $schedule Cron schedule string (e.g. '8 * * * *').  The
     *      schedule can handle ranges (10-12) and intervals
     *      (*\/10 [remove the backslash]).  Schedule parts should map to
     *      minute [0-59], hour [0-23], day of month, month [1-12], day of week [1-7]
     *
     * @throws InvalidArgumentException if $schedule is not a valid cron schedule
     */
    public function __construct($schedule)
    {
        $this->_cronParts = explode(' ', $schedule);
        if (count($this->_cronParts) != 5) {
            throw new InvalidArgumentException($schedule . ' is not a valid cron schedule string');
        }
    }

    /**
     * Check if a date/time unit value satisfies a crontab unit
     *
     * @param DateTime $nextRun Current next run date
     * @param string $unit Date/time unit type (e.g. Y, m, d, H, i)
     * @param string $schedule Cron schedule variable
     *
     * @return bool Returns TRUE if the unit satisfies the constraint
     */
    public function unitSatisfiesCron(DateTime $nextRun, $unit, $schedule)
    {
        $unitValue = (int)$nextRun->format($unit);

        if ($schedule == '*') {
            return true;
        } if (strpos($schedule, '-')) {
            list($first, $last) = explode('-', $schedule);
            return $unitValue >= $first && $unitValue <= $last;
        } else if (strpos($schedule, '*/') !== false) {
            list($delimiter, $interval) = explode('*/', $schedule);
            return $unitValue % (int)$interval == 0;
        } else {
            return $unitValue == (int)$schedule;
        }
    }

    /**
     * Get the date in which the cron will run next
     *
     * @param string|DateTime (optional) $fromTime Set the relative start time
     * @param string $currentTime (optional) Optionally set the current date
     *      time for testing purposes
     *
     * @return DateTime
     */
    public function getNextRunDate($fromTime = 'now', $currentTime = 'now')
    {
        $nextRun = ($fromTime instanceof DateTime) ? $fromTime : new DateTime($fromTime ?: 'now');
        $nextRun->setTime($nextRun->format('H'), $nextRun->format('i'), 0);
        $currentDate = ($currentTime instanceof DateTime) ? $currentTime : new DateTime($currentTime ?: 'now');
        $i = 0;

        // Set a hard limit to bail on an impossible date
        while (++$i && $i < 100000) {

            // Adjust the month until it matches.  Reset day to 1 and reset time.
            if (!$this->unitSatisfiesCron($nextRun, 'm', $this->getSchedule('month'))) {
                $nextRun->add(new DateInterval('P1M'));
                $nextRun->setDate($nextRun->format('Y'), $nextRun->format('m'), 1);
                $nextRun->setTime(0, 0, 0);
                continue;
            }

            // Adjust the day of the month by incrementing the day until it matches. Reset time.
            if (!$this->unitSatisfiesCron($nextRun, 'd', $this->getSchedule('day_of_month'))) {
                $nextRun->add(new DateInterval('P1D'));
                $nextRun->setTime(0, 0, 0);
                continue;
            }

            // Adjust the day of week by incrementing the day until it matches.  Resest time.
            if (!$this->unitSatisfiesCron($nextRun, 'N', $this->getSchedule('day_of_week'))) {
                $nextRun->add(new DateInterval('P1D'));
                $nextRun->setTime(0, 0, 0);
                continue;
            }

            // Adjust the hour until it matches the set hour.  Set seconds and minutes to 0
            if (!$this->unitSatisfiesCron($nextRun, 'H', $this->getSchedule('hour'))) {
                $nextRun->add(new DateInterval('PT1H'));
                $nextRun->setTime($nextRun->format('H'), 0, 0);
                continue;
            }

            // Adjust the minutes until it matches a set minute
            if (!$this->unitSatisfiesCron($nextRun, 'i', $this->getSchedule('minute'))) {
                $nextRun->add(new DateInterval('PT1M'));
                continue;
            }

            // If the suggested next run time is not after the current time, then keep iterating
            if (is_string($fromTime) && $currentDate >= $nextRun) {
                $nextRun->add(new DateInterval('PT1M'));
                continue;
            }

            break;
        }

        return $nextRun;
    }

    /**
     * Get all or part of the cron schedule string
     *
     * @param string $part Specify the part to retrieve or NULL to get the full
     *      cron schedule string.  $part can be the PHP date() part of a date
     *      formatted string or one of the following values:
     *      NULL, 'minute', 'hour', 'month', 'day_of_week', 'day_of_month'
     *
     * @return string
     */
    public function getSchedule($part = null)
    {
        switch ($part) {
            case 'minute': case 'i':
                return $this->_cronParts[0];
            case 'hour': case 'H':
                return $this->_cronParts[1];
            case 'day_of_month': case 'd':
                return $this->_cronParts[2];
            case 'month': case 'm':
                return $this->_cronParts[3];
            case 'day_of_week': case 'N':
                return $this->_cronParts[4];
            default:
                return implode(' ', $this->_cronParts);
        }
    }

    /**
     * Deterime if the cron is due to run based on the current time, last run
     * time, and the next run time.
     *
     * If the relative next run time based on the last run time is not equal to
     * the next suggested run time based on the current time, then the cron
     * needs to run.
     *
     * @param string|DateTime $lastRun (optional) Date the cron was last run.
     * @param string|DateTime $currentTime (optional) Set the current time for testing
     *
     * @return bool Returns TRUE if the cron is due to run or FALSE if not
     */
    public function isDue($lastRun = 'now', $currentTime = 'now')
    {
        $currentTime = ($currentTime instanceof DateTime) ? $currentTime : new DateTime($currentTime ?: 'now');
        return ($this->getNextRunDate($lastRun, $currentTime) != $this->getNextRunDate($currentTime, $currentTime));
    }
}