<?php

namespace Application\Service;

use Laminas\Session\Container;
use Application\Service\CommonService;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;

class DashboardService
{

    public $sm = null;

    public function __construct($sm = null)
    {
        $this->sm = $sm;
    }

    public function getServiceManager()
    {
        return $this->sm;
    }

    public function getQuickStats()
    {
        $certificationDb = $this->sm->get('Certification\Model\CertificationTable');
        return $certificationDb->getQuickStats();
    }

    public function getCertificationPieChartResults($params)
    {
        $certificationDb = $this->sm->get('Certification\Model\CertificationTable');
        return $certificationDb->getCertificationPieChartResults($params);
    }

    public function getCertifiedProvinceChartResults($params)
    {
        $certificationDb = $this->sm->get('Certification\Model\CertificationTable');
        return $certificationDb->getCertifiedProvinceChartResults($params);
    }

    public function getCertifiedDistrictChartResults($params)
    {
        $certificationDb = $this->sm->get('Certification\Model\CertificationTable');
        return $certificationDb->getCertifieDistrictChartResults($params);
    }

    public function getCertificationBarChartResults($params)
    {
        $certificationDb = $this->sm->get('Certification\Model\CertificationTable');
        return $certificationDb->getCertificationBarChartResults($params);
    }

    public function getCertificationMapResults($params)
    {
        $certificationDb = $this->sm->get('Certification\Model\CertificationTable');
        return $certificationDb->getCertificationMapResults($params);
    }

    public function getTesters($parameters)
    {
        $certificationDb = $this->sm->get('Certification\Model\ProviderTable');
        return $certificationDb->fetchTesters($parameters);
    }

    public function getWrittenExamAverageRadarResults($params)
    {
        $writtenExamDb = $this->sm->get('Certification\Model\WrittenExamTable');
        return $writtenExamDb->fetchWrittenExamAverageRadarResults($params);
    }

    public function getPracticalExamAverageBarResults($params)
    {
        $writtenExamDb = $this->sm->get('Certification\Model\PracticalExamTable');
        return $writtenExamDb->fetchPracticalExamAverageBarResults($params);
    }

    public function getPracticalWrittenCountResults($params)
    {
        $writtenExamDb = $this->sm->get('Certification\Model\PracticalExamTable');
        return $writtenExamDb->fecthPracticalWrittenCountResults($params);
    }
}
