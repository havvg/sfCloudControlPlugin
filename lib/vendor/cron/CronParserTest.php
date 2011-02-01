<?php

require_once('CronParser.class.php');

/**
 * Cron parser test
 */
class CronParserTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers CronParser::__construct
     * @covers CronParser::getSchedule
     */
    public function test__construct()
    {
        $cron = new CronParser('1 2-4 * 4 */3', '2010-09-10 12:00:00');
        $this->assertEquals('1', $cron->getSchedule('minute'));
        $this->assertEquals('2-4', $cron->getSchedule('hour'));
        $this->assertEquals('*', $cron->getSchedule('day_of_month'));
        $this->assertEquals('4', $cron->getSchedule('month'));
        $this->assertEquals('*/3', $cron->getSchedule('day_of_week'));
        $this->assertEquals('1 2-4 * 4 */3', $cron->getSchedule());
    }

    /**
     * @covers CronParser::__construct
     * @expectedException InvalidArgumentException
     */
    public function test__constructException()
    {
        // Only four values
        $cron = new CronParser('* * * 1');
    }

    /**
     * Data provider for cron schedule
     *
     * @return array
     */
    public function scheduleProvider()
    {
        return array(
            //    schedule,            current time,          last run,              next run,              is due
            // every 2 minutes, every 2 hours
            array('*/2 */2 * * *',     '2010-08-10 21:47:27', '2010-08-10 15:30:00', '2010-08-10 22:00:00', true),
            // every minute
            array('* * * * *',         '2010-08-10 21:50:37', '2010-08-10 21:00:00', '2010-08-10 21:51:00', true),
            // Minutes 7-9, every 9 days
            array('7-9 * */9 * *',     '2010-08-10 22:02:33', '2010-08-10 22:01:33', '2010-08-18 00:07:00', false),
            // Minutes 12-19, every 3 hours, every 5 days, in June, on Sunday
            array('12-19 */3 */5 6 7', '2010-08-10 22:05:51', '2010-08-10 22:04:51', '2011-06-05 00:12:00', false),
            // 15th minute, of the second hour, every 15 days, in January, every Friday
            array('15 2 */15 1 */5',   '2010-08-10 22:10:19', '2010-08-10 22:09:19', '2015-01-30 02:15:00', false),
            // 15th minute, of the second hour, every 15 days, in January, Tuesday-Friday
            array('15 2 */15 1 2-5',   '2010-08-10 22:10:19', '2010-08-10 22:09:19', '2013-01-15 02:15:00', false)
        );
    }

    /**
     * @covers CronParser::isDue
     * @covers CronParser::getNextRunDate
     * @dataProvider scheduleProvider
     */
    public function testIsDueNextRun($schedule, $relativeTime, $lastRun, $nextRun, $isDue)
    {
        $cron = new CronParser($schedule);
        $this->assertEquals($cron->isDue(new DateTime($lastRun), new DateTime($relativeTime)), $isDue);
        $this->assertEquals(new DateTime($nextRun), $cron->getNextRunDate($lastRun, $relativeTime));
    }
}