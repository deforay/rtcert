<?php

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class HumanReadableDateFormat extends AbstractHelper
{

    // $inputFormat = yyyy-mm-dd
    public function __invoke($dateIn)
    {
        if ($dateIn == null || $dateIn == "" || $dateIn == "0000-00-00" || $dateIn == "0000-00-00 00:00:00") {
            return '';
        } else {
            return \Application\Service\CommonService::humanReadableDateFormat($dateIn);
        }
    }
}
