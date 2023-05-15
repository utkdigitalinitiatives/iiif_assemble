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
            $start = $this->choose_format($date_created[0]);
            $end = $this->choose_format($date_created[1]);
            return $this->__get_middle_date($start, $end);
        }
        else {
            return $this->choose_format($date_created[0]);
        }
    }

    private function choose_format($date) {
        $formats = [
            'Y',
            'Y-m',
            'Y-m-d'
        ];
        $current_date = null;
        foreach ($formats as $format) {
            $current_date = DateTime::createFromFormat($format, $date);
            if ($current_date !== false) {
                break;
            }
        }
        if ($current_date !== false) {
            $formattedDate = $current_date->format('Y-m-d\TH:i:s');
            return $formattedDate;
        }
        return $current_date;
    }

    private function __get_middle_date($date1, $date2)
    {
        $timestamp1 = strtotime($date1);
        $timestamp2 = strtotime($date2);
        $middleTimestamp = ($timestamp1 + $timestamp2) / 2;
        return date('Y-m-d\TH:i:s', $middleTimestamp);
    }
}