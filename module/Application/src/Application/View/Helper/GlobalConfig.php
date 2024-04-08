<?php

namespace Application\View\Helper;

use Application\Model\GlobalTable;
use Laminas\View\Helper\AbstractHelper;

class GlobalConfig extends AbstractHelper
{
    public GlobalTable $globalTable;
    public function __construct($globalTable)
    {
        $this->globalTable = $globalTable;
    }

    public function __invoke()
    {
        return $this->globalTable->getGlobalConfig();
    }
}
