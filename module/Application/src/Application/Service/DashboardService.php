<?php

namespace Application\Service;

use Certification\Model\CertificationTable;
use Certification\Model\PracticalExamTable;
use Certification\Model\WrittenExamTable;

final class DashboardService
{

    public $sm = null;
    private $writtenExamTable;
    private $practicalExamTable;
    private $certificationTable;

    public function __construct(
        $sm = null,
        CertificationTable $certificationTable,
        WrittenExamTable $writtenExamTable,
        PracticalExamTable $practicalExamTable
    ) {
        $this->sm = $sm;
        $this->writtenExamTable = $writtenExamTable;
        $this->practicalExamTable = $practicalExamTable;
        $this->certificationTable = $certificationTable;
    }

    public function getServiceManager()
    {
        return $this->sm;
    }

    public function getQuickStats()
    {
        return $this->certificationTable->getQuickStats();
    }

    public function getCertificationPieChartResults($params)
    {
        return $this->certificationTable->getCertificationPieChartResults($params);
    }

    public function getCertifiedProvinceChartResults($params)
    {
        return $this->certificationTable->getCertifiedProvinceChartResults($params);
    }

    public function getCertifiedDistrictChartResults($params)
    {
        return $this->certificationTable->getCertifieDistrictChartResults($params);
    }

    public function getCertificationBarChartResults($params)
    {
        return $this->certificationTable->getCertificationBarChartResults($params);
    }

    public function getCertificationMapResults($params)
    {
        return $this->certificationTable->getCertificationMapResults($params);
    }

    public function getTesters($parameters)
    {
        $providerDb = $this->sm->get('Certification\Model\ProviderTable');
        return $providerDb->fetchTesters($parameters);
    }

    public function getWrittenExamAverageRadarResults($params)
    {
        return $this->writtenExamTable->fetchWrittenExamAverageRadarResults($params);
    }

    public function getPracticalExamAverageBarResults($params)
    {
        return $this->practicalExamTable->fetchPracticalExamAverageBarResults($params);
    }

    public function getPracticalWrittenCountResults($params)
    {
        return $this->practicalExamTable->fecthPracticalWrittenCountResults($params);
    }
}
