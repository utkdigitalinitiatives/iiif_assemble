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
        $datetime_1 = $this->format($date1);
        $datetime_2 = $this->format($date2);
        $interval = $datetime_1->diff($datetime_2);
        $middleDate = $datetime_1->add($interval->divide(2));
        return $middleDate->form('Y-m-d\TH:i:s');
    }
}