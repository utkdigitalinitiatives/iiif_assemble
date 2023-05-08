<?php

namespace Src;

class Navdate
{
    public function __construct($mods)
    {
        $this->data = $mods;
        $this->date = $this->select_best_date();
    }


    private function __select_best_date()
    {
        $date_created = $this->data->query('originInfo/dateCreated[@encoding="edtf"]');
        return $date_created;
    }

    public function format() {
        $dateTime = new DateTime($this->date);
        return $dateTime->format('Y-m-d\TH:i:s');
    }
}