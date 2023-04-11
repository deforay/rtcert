<?php

namespace Application\Model;

use Laminas\Session\Container;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\TableGateway\AbstractTableGateway;
use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;
use Application\Service\CommonService;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Countries
 *
 * @author Thanaseelan
 */
class TestOptionsTable extends AbstractTableGateway {

    protected $table = 'test_options';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
}
