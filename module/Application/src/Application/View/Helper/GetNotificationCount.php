<?php

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Certification\Model\CertificationTable;

class getNotificationCount extends AbstractHelper
{
    public CertificationTable $certificationTable;
    public function __construct($certificationTable)
    {
        $this->certificationTable = $certificationTable;
    }

    public function __invoke()
    {
        return $this->certificationTable->getNotificationCount();
    }
}
