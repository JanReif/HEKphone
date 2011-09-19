<?php

/**
 * Rates
 *
 * This class has been auto-generated by the Doctrine ORM Framework
 *
 * @package    hekphone
 * @subpackage model
 * @author     Your name here
 * @version    SVN: $Id: Builder.php 7490 2010-03-29 19:53:27Z jwage $
 */

class Rates extends BaseRates
{
    /**
     * fetches the charge for a given date and a given length of a call
     *
     * @param $duration [sec]
     * @param $date [date] (defaults to now)
     * @return integer [euro cents]
     */
    public function getCharge($duration, $date="now") {
        if ($date == "now")
            $date = date("H:i:s", time());

        if ( ! $timestamp = strtotime($date)) {
            throw new Exception("$date is an invalid date.");
        }

        $time = date("H:i:s", $timestamp);
        if ($time <= $this->primary_time_begin || $time >=$this->secondary_time_begin && $this->secondary_time_begin != NULL) {
            $ratePerSecond = $this->secondary_time_rate/60;
        } else {
            $ratePerSecond = $this->primary_time_rate/60;
        }

        if ($duration > 0) {
            // Pulsing is of the format 'N/M' where N/M
            //   * N defines the first pulsing length
            //   * M defines the lenght of every following pulse
            // There are always full pulses charged. Example:
            // If the call is 110 seconds long and the pulsing is 60/30
            // 120 seconds (1*60+2*30) are charged.
            $pulseLenght = explode('/', $this->pulsing);

            if(count($pulseLenght) != 2) {
                throw new Exception($this->pulsing . " is not a well formed pulsing.");
            }

            $charge = $pulseLenght[0] * $ratePerSecond;
            if($duration > $pulseLenght[0]) {
                $additionalPulsesToCharge = ceil(($duration - $pulseLenght[0])/$pulseLenght[1]);
                $charge += $additionalPulsesToCharge * $pulseLenght[1] * $ratePerSecond;
            }
        } else {
            $charge = 0;
        }

        return $charge;
    }
}