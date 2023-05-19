<?php

namespace Src;

use DateTime;

class Navdate
{
    private $data;
    public function __construct($mods)
    {
        $this->data = $mods;
        $this->date = $this->selectBestDate();
    }

    private function selectBestDate()
    {
        $dateCreated = $this->data->query('originInfo/dateCreated[@encoding="edtf"]');
        $dateIssued = $this->data->query('originInfo/dateIssued[@encoding="edtf"]');

        if (is_array($dateCreated)) {
            return $this->processBestDate($dateCreated);
        } elseif (is_array($dateIssued)) {
            return $this->processBestDate($dateIssued);
        } else {
            return null;
        }
    }

    private function processBestDate($dates)
    {
        if (count($dates) == 2) {
            $start = $this->chooseFormat($dates[0]);
            $end = $this->chooseFormat($dates[1]);
            return $this->getMiddleDate($start, $end);
        } else {
            return $this->chooseFormat($dates[0]);
        }
    }

    private function chooseFormat($date)
    {
        $formats = [
            'Y',
            'Y-m',
            'Y-m-d'
        ];

        foreach ($formats as $format) {
            $currentDate = DateTime::createFromFormat($format, $date);
            if ($currentDate !== false) {
                return $currentDate->format('Y-m-d\TH:i:s\Z');
            }
        }

        return null;
    }

    private function getMiddleDate($date1, $date2)
    {
        $timestamp1 = strtotime($date1);
        $timestamp2 = strtotime($date2);
        $middleTimestamp = ($timestamp1 + $timestamp2) / 2;
        return date('Y-m-d\TH:i:s\Z', $middleTimestamp);
    }
}