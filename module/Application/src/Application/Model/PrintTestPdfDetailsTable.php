<?php

namespace Application\Model;

use Zend\Db\Adapter\Adapter;
use Zend\Db\TableGateway\AbstractTableGateway;
use Zend\Db\Sql\Sql;
use Application\Service\CommonService;
use Zend\Session\Container;
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of Countries
 *
 * @author thanaseelan
 */
class PrintTestPdfDetailsTable extends AbstractTableGateway {

    protected $table = 'print_test_pdf_details';

    public function __construct(Adapter $adapter) {
        $this->adapter = $adapter;
    }
}