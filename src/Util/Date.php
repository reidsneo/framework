<?php 

namespace Neko\Framework\Util;

class Date 
{

    public static function verifyDate($date)
    {
        if($date == null){
            $date = date('Y-m-d');
        }
        return (\DateTime::createFromFormat('m/d/Y', $date) !== false);
    }
    
    public static function ago($datetime, $full = false) {
        $now = new \DateTime;
        $ago = new \DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }

    public static function convertDate($time) {
        return date("Y-m-d", $time);
    }


    public static function now() {
        return date("Y-m-d H:i:s");
    }

    public static function dateOnly() {
        return date("Y-m-d");
    }

    public static function timeOnly() {
        return date("H:i:s");
    }

    public static function secondsToTimeAlt($seconds) 
    {

        $dtF = new \DateTime('@0');
        $dtT = new \DateTime("@$seconds");
        return $dtF->diff($dtT)->format('%a days, %h hours and %i minutes');

    }

    public static function secondsToTime($inputSeconds) {
        $secondsInAMinute = 60;
        $secondsInAnHour = 60 * $secondsInAMinute;
        $secondsInADay = 24 * $secondsInAnHour;
    
        // Extract days
        $days = floor($inputSeconds / $secondsInADay);
    
        // Extract hours
        $hourSeconds = $inputSeconds % $secondsInADay;
        $hours = floor($hourSeconds / $secondsInAnHour);
    
        // Extract minutes
        $minuteSeconds = $hourSeconds % $secondsInAnHour;
        $minutes = floor($minuteSeconds / $secondsInAMinute);
    
        // Extract the remaining seconds
        $remainingSeconds = $minuteSeconds % $secondsInAMinute;
        $seconds = ceil($remainingSeconds);
    
        // Format and return
        $timeParts = [];
        $sections = [
            'day' => (int)$days,
            'hour' => (int)$hours,
            'minute' => (int)$minutes,
            'second' => (int)$seconds,
        ];
    
        foreach ($sections as $name => $value){
            if ($value > 0){
                $timeParts[] = $value. ' '.$name.($value == 1 ? '' : 's');
            }
        }
    
        return implode(', ', $timeParts);
    }

    public static function getTotalWorkDays($start, $end, $holidays = [], $weekends = ['Sat', 'Sun']){

        $start = new \DateTime($start);
        $end   = new \DateTime($end);
        $end->modify('+1 day');
    
        $total_days = $end->diff($start)->days;
        $period = new \DatePeriod($start, new \DateInterval('P1D'), $end);
    
        foreach($period as $dt) {
            if (in_array($dt->format('D'),  $weekends) || in_array($dt->format('Y-m-d'), $holidays)){
                $total_days--;
            }
        }
        return $total_days;
    }

}
