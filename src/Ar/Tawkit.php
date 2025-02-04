<?php 

namespace Adnane\Arabic\Ar;

use Adnane\Arabic\Ar\data\tawkit\loader;
use Adnane\Arabic\Arabic;

class Tawkit
{   
    /* 
    | ===================================================
    |
    | Working With Hijri Date 
    | 
    | dealing with transforming unix time to hijri date follows the process
    | 1 - Converts from a supported calendar to Julian Day Count (تقويم ميلادي)
    | 2 - Converts Julian Day Count To Hijri Month , Day , Year (orders only) 
    | 3 - Describe Result Date based on Converting Result 
    | ===================================================
    */
    
    private static $hijri;
    
    private static $since = 'منذ';
    private static $in = 'في';
    private static $and = ' و ';
    private static $now = ' الان ';
    private static $at = ' في ';
    private static $the = 'الـ';
    
    private static $next = ' القادم';
    private static $previous = ' الفارط';
    private static $feminin = 'ة';


    use loader;
    
    /* 
    | format date to be like : 12/10/1442 هـ
    */
    private static function formatNumeric()
    {
        return self::$hijri[1].' / '.self::$hijri[0].' / '.self::$hijri[2].' هـ ';
    }

    /* 
    | format date to be like : العاشر من شعبان من السنة الهجرية ألف و أربع مئة و اثنان و أربعون
    */
    private static function formatFull()
    {
        return Arabic::tartib(self::$hijri[1]) . ' من ' /*day*/
        
        . self::monthName(self::$hijri[0]) . ' من السنة الهجرية ' /*month*/
        
        . Arabic::tafkit(self::$hijri[2]); /*year*/
    }
    
    /* 
    | get the month hijri name depending 
    | on its order in hijri year 
    */
    public static function monthName($i)
    {
        $month  = loader::load()['hijri_months'];

        return $month[$i];
    }

    /* 
    | format date to be like : 1442 / شعبان / 10
    */
    private static function formatStandard()
    {
        return self::$hijri[2] .'/'
        . self::monthName(self::$hijri[0])
        .'/'.self::$hijri[1] .' هـ ';
    }    
    
    /* 
    | Retrieve the full hijri date in a given format 
    | f : stands for -> full date 
    | s : stands for -> standard date 
    | n : stands for -> numeric date 
    | 
    */ 
    public static function fullHijri($date , $format = 'f')
    {
        if(!$date) $time = time();
        else $time = strtotime($date);
         
        self::$hijri = self::GreToHijri($time);

        switch ($format) {
            case 'f': 
                return self::formatFull();
                break;
            case 's': 
                return self::formatStandard();
                break;
            case 'n': 
                return self::formatNumeric();
                break;    
            default:
                return self::formatFull();
                break;
        }
    }
    
    /* 
    | this function converts gregorian time to hijri time
    | 
    | it uses the function cal_to_jd 
    | that means that it turns a given calendar time
    | to julian day count 
    | 
    | The Julian day is the continuous count
    | of days since the beginning of the Julian period
    |  julian period starts from : Jan , 1 , 4713 BC 
    |
    | the method cal_to_jd , handle that and count the days 
    | past between 4713 BC and the target given time 
    |
    | example of response to GreToHijri() : [ 0 => 8 , 1 => 10 ,2 => 1442 ]
    |  
    */
    public static function GreToHijri($time = null)
    {
        if ($time === null) { $time = time(); }
        
        $date = [
            'm'=> date('m', $time),
            'd'=> date('d', $time),
            'y'=> date('Y', $time),
        ];
        
        $julian_day_count = cal_to_jd(CAL_GREGORIAN, $date['m'], $date['d'], $date['y']);
        
        return self::JDToHijri($julian_day_count);
    }

    /* 
    | this function converts julian day count to hijri month , year , day 
    | 
    | as know , the islamic takwim started at :  16 july 622 
    | that means , a 197 days from 622 were past since the takwim started 
    | 
    | thus : ( 612 After Birth + 4713 Before Birth ) years plus the 197 days 
    | is the julian day count between the two periods 
    | 
    | this equals to 1948440 
    */
    private static function JDToHijri($jd)
    {
        $jd = $jd - 1948440 + 10633;

        $n  = (int)(($jd - 1) / 10631);
        
        $jd = $jd - 10631 * $n + 354;
        
        $j  = ((int)((10985 - $jd) / 5316)) *
            ((int)(50 * $jd / 17719)) +
            ((int)($jd / 5670)) *
            ((int)(43 * $jd / 15238));
        
        $jd = $jd - ((int)((30 - $j) / 15)) *
            ((int)((17719 * $j) / 50)) -
            ((int)($j / 16)) *
            ((int)((15238 * $j) / 43)) + 29;
        
        $m  = (int)(24 * $jd / 709);
        
        $d  = $jd - (int)(709 * $m / 24);
        
        $y  = 30 * $n + $j - 30;

        return array($m, $d, $y);
    }

    /* 
    | ===================================================
    |
    | Working With Relative time
    | 
    | ===================================================
    */
    
    /* 
    | manage different related class's functions and return simplified results 
    | count time between two given dates 
    | example : 1900/12/12 -> "منذ مئة و عشرون سنة"
    */
    public static function relativeTime($date ,$date2 ,$detailed)
    {
        $r = self::standardRelative($date ,$date2);
        $r = self::handleSpecials($r);
        
        if($date2)
        {

            if($detailed) return self::diff($date ,$date2);
        
            return $r;
        }
      
        if($detailed) return self::diff($date ,$date2);
        
        if(strtotime($date) - time() > 0) return self::$in.' '.$r;

        if(strtotime($date) - time() == 0) return self::$now;
        
        return self::$since.' '.$r;
    }
    
    /* 
    | handle and clean string depending on unique strings in arabic 
    | example : منذ اثنان سنة will be منذ سنتين
    */
    private static function handleSpecials($str)
    {
        $data = loader::load()['uniques'];
        
        foreach ($data as $u) {
            $str = str_replace(array_search($u ,$data), $u ,$str);
        }

        return $str;
    }
    
    /* 
    | count the difference between two given dates :
    | seconds , years , months ,weeks , days , hours , minutes 
    | from a date 
    | example : diff("1900/12/12")
    | [
    |   "y" => 120,
    |  "m" => 1568,
    |  "w" => 6275,
    |  "d" => 43931,
    |  "h" => 1054360,
    |  "mn" => 63261602,
    |  "s" => 3795696173
    |]
    */
    private static function diff($date ,$date2 = null)
    {
        $time = time();
        
        if($date2) $time = strtotime($date2);

        $seconds = abs(strtotime($date) - $time);
        return 
        [
            'y' => intval(floor($seconds / (3600 * 24 * 365.25))),
            'm' => intval(floor($seconds / (3600 * 24 * 7 * 4))),
            'w' => intval(floor($seconds / (3600 * 24 * 7))),
            'd' => intval(floor($seconds / (3600 * 24))),
            'h' => intval(floor($seconds / (3600))),
            'mn' => intval(floor($seconds / (60))),
            's' => intval(floor($seconds)),
        ];         
    }

    /* 
    | get the relative time , depending on the greatest level found in diff()
    | example : 
    |  [
    |   "y" => 120,
    |   "m" => 1568,
    |   "w" => 6275,
    |   "d" => 43931,
    |   "h" => 1054360,
    |   "mn" => 63261602,
    |   "s" => 3795696173
    |  ]
    | == > no need for saying 120 years and 1586 months and ... ect 
    | == > we better say : since 120 years 
    */
    private static function standardRelative($date ,$date2 = null)
    {
        $levels = self::diff($date ,$date2);
        
        $data = loader::load()['time'];

        foreach($levels as $level)
        {
            if($level !== 0)
            {
                $levelSymbol = array_search($level ,$levels);

                if($level > 1)
                {
                    if($level <= 9 && $level > 1)
                    {
                        return Arabic::tafkit( $level ) .' '. $data[$levelSymbol]['p'];
                    }
                    return Arabic::tafkit( $level ) .' '. $data[$levelSymbol]['s'];
                }
                else
                {
                    return $data[$levelSymbol]['s'];
                }

            }
        }
    }

}