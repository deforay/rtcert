<?php

namespace Application\View\Helper;

use Application\Model\Acl;
use Laminas\View\Helper\AbstractHelper;

class IsAllowed extends AbstractHelper
{
    protected $acl;

    public function __construct(Acl $acl)
    {
        $this->acl = $acl;
    }

    public function __invoke($role, $resource, $privilege)
    {
        return $this->acl->isAllowed($role, $resource, $privilege);
    }
}
