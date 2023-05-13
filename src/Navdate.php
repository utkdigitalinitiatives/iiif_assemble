<?php

namespace Src;

use DateTime;

class Navdate
{
    public function __construct($mods)
    {
        $this->data = $mods;
        $this->date = $this->__select_best_date();
    }


    private function __select_best_date()
    {
        $date_created = $this->data->query('originInfo/dateCreated[@encoding="edtf"]');
        if (count($date_created) == 2) {
            return $this->__get_middle_date($date_created[0], $date_created[1]);
        }
        else {
            return $this->format($date_created[0]);
        }
    }

    private function format($date) {
        $dateTime = new DateTime($date);
        return $dateTime->format('Y-m-d\TH:i:s');
    }

    private function __get_middle_date($date1, $date2)
    {
        $timestamp1 = strtotime($date1);
        $timestamp2 = strtotime($date2);
        $middleTimestamp = ($timestamp1 + $timestamp2) / 2;
        return date('Y-m-d\TH:i:s', $middleTimestamp);
    }
}