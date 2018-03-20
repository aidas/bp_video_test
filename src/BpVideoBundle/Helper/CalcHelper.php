<?php

namespace BpVideoBundle\Helper;

class CalcHelper
{
    public static function calculateMedian($arr) {
        sort($arr);
        $count = count($arr);
        $middleVal = floor(($count-1)/2);
        if($count % 2) {
            $median = $arr[$middleVal];
        } else {
            $low = $arr[$middleVal];
            $high = $arr[$middleVal+1];
            $median = (($low+$high)/2);
        }
        return $median;
    }
}
