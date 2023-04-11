<?php

namespace Application\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class GetNotificationCount extends AbstractHelper
{
    public \Certification\Model\CertificationTable $certificationTable;
    public function __construct($certificationTable)
    {
        $this->certificationTable = $certificationTable;
    }

    public function __invoke()
    {
        return $this->certificationTable->getNotificationCount();
    }
}
