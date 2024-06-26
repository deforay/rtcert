<?php

namespace Application\Service;

use Laminas\Session\Container;
use Application\Service\CommonService;
use Laminas\Db\Adapter\Adapter;
use Laminas\Db\Sql\Sql;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class FacilityService
{

    public $sm = null;

    public function __construct($sm)
    {
        $this->sm = $sm;
    }

    public function getServiceManager()
    {
        return $this->sm;
    }

    public function addFacility($params)
    {
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $facilityDb = $this->sm->get('SpiRtFacilitiesTable');
            $result = $facilityDb->addFacilityDetails($params);
            if ($result > 0) {
                $adapter->commit();
                //<-- Event log
                $subject = $result;
                $eventType = 'facility-add';
                $action = 'added a new facility ' . $params['facilityName'];
                $resourceName = 'Facility';
                $eventLogDb = $this->sm->get('EventLogTable');
                $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
                //-------->
                $container = new Container('alert');
                $container->alertMsg = 'Facility details added successfully';
                return $result;
            }
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function updateFacility($params)
    {
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $facilityDb = $this->sm->get('SpiRtFacilitiesTable');
            $result = $facilityDb->updateFacilityDetails($params);
            if ($result > 0) {
                $adapter->commit();
                //<-- Event log
                $subject = $result;
                $eventType = 'facility-update';
                $action = 'updated a facility ' . $params['facilityName'];
                $resourceName = 'Facility';
                $eventLogDb = $this->sm->get('EventLogTable');
                $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
                //-------->
                $container = new Container('alert');
                $container->alertMsg = 'Facility details updated successfully';
                return $result;
            }
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getAllFacilities($parameters)
    {
        $facilityDb = $this->sm->get('SpiRtFacilitiesTable');
        $acl = $this->sm->get('AppAcl');
        return $facilityDb->fetchAllFacilities($parameters, $acl);
    }

    public function getFacility($id)
    {
        $facilityDb = $this->sm->get('SpiRtFacilitiesTable');
        return $facilityDb->fetchFacility($id);
    }

    public function getFacilityList($val)
    {
        $facilityDb = $this->sm->get('SpiRtFacilitiesTable');
        return $facilityDb->fetchFacilityList($val);
    }

    public function addEmail($params)
    {
        $result = 0;
        $container = new Container('alert');
        $auditMailDb = $this->sm->get('AuditMailTable');
        $facilityDb = $this->sm->get('SpiRtFacilitiesTable');
        $db = $this->sm->get('SpiFormVer3Table');
        $commonService = new \Application\Service\CommonService();
        $config = new \Laminas\Config\Reader\Ini();
        $configResult = $config->fromFile(CONFIG_PATH . '/custom.config.ini');
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $fromName = $configResult['admin']['name'];
            $fromEmailAddress = $configResult['admin']['emailAddress'];
            $toName = ucwords($params['facilityName']);
            $toEmailAddress = trim($params['emailAddress']);
            $cc = $configResult['admin']['emailAddress'];
            $subject = 'SPI-RT-CHECKLIST';
            $message = '';
            $message .= '<table border="0" cellspacing="0" cellpadding="0" style="width:100%;background-color:#DFDFDF;">';
            $message .= '<tr><td align="center">';
            $message .= '<table cellpadding="3" style="width:92%;font-family:Helvetica,Arial,sans-serif;margin:30px 0px 30px 0px;padding:2% 0% 0% 2%;background-color:#ffffff;">';
            $message .= '<tr><td>Hi <strong>' . ucwords($params['facilityName']) . '</strong>,</td></tr>';
            $message .= '<tr><td><p>' . ucfirst(trim($params['message'])) . '</p></td></tr>';
            $message .= '</table>';
            $message .= '</tr></td>';
            $message .= '</table>';
            $mailId = $auditMailDb->insertAuditMailDetails($toEmailAddress, $cc, $subject, $message, $fromName, $fromEmailAddress);
            if ($mailId > 0) {
                $result = $facilityDb->updateFacilityEmailAddress($params);
                if ($result > 0) {
                    if (!file_exists(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . "audit-email") && !is_dir(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . "audit-email")) {
                        mkdir(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . "audit-email");
                    }

                    if (!file_exists(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . "audit-email" . DIRECTORY_SEPARATOR . $mailId) && !is_dir(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . "audit-email" . DIRECTORY_SEPARATOR . $mailId)) {
                        mkdir(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . "audit-email" . DIRECTORY_SEPARATOR . $mailId);
                    }

                    //Move Attachement File(s)
                    $errorAttachement = 0;
                    if (isset($_FILES['attchement']['name']) && count($_FILES['attchement']['name']) > 0) {
                        $counter = count($_FILES['attchement']['name']);
                        for ($attch = 0; $attch < $counter; $attch++) {
                            if (trim($_FILES['attchement']['name'][$attch]) != '') {
                                $extension = strtolower(pathinfo(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $_FILES['attchement']['name'][$attch], PATHINFO_EXTENSION));
                                $fileName = \Application\Service\CommonService::generateRandomString(5) . "." . $extension;
                                if (move_uploaded_file($_FILES["attchement"]["tmp_name"][$attch], TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . "audit-email" . DIRECTORY_SEPARATOR . $mailId . DIRECTORY_SEPARATOR . $fileName)) {
                                } else {
                                    $errorAttachement += 1;
                                }
                            }
                        }
                    }
                    $adapter->commit();
                    if ($errorAttachement > 0) {
                        if ($errorAttachement > 1) {
                            $container->alertMsg = $errorAttachement . 'attachements were failed to upload!';
                        } else {
                            $container->alertMsg = $errorAttachement . 'attachement was failed to upload!';
                        }
                    } else {
                        $container->alertMsg = 'Mail queue added successfully.';
                    }
                } else {
                    $mailId = 0;
                    $container->alertMsg = 'We have experienced the problem..Please try again!';
                }
            } else {
                $container->alertMsg = 'We have experienced the problem..Please try again!';
            }
            return $mailId;
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getAllTestingPoints($parameters)
    {
        $sbiFormDb = $this->sm->get('SpiFormVer3Table');
        $acl = $this->sm->get('AppAcl');
        return $sbiFormDb->fetchAllTestingPointsBasedOnFacility($parameters, $acl);
    }

    public function getFacilityProfileByAudit($ids)
    {
        $facilityDb = $this->sm->get('SpiRtFacilitiesTable');
        return $facilityDb->fetchFacilityProfileByAudit($ids);
    }

    public function getProvinceList()
    {
        $facilityDb = $this->sm->get('SpiRtFacilitiesTable');
        return $facilityDb->fetchProvinceList();
    }

    public function mapProvince($params)
    {
        $facilityDb = $this->sm->get('SpiRtFacilitiesTable');
        return $facilityDb->mapProvince($params);
    }
    public function exportFacility()
    {
        try {
            $common = new \Application\Service\CommonService();
            $queryContainer = new Container('query');
            $excel = new Spreadsheet();
            $sheet = $excel->getActiveSheet();
            $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
            $sql = new Sql($dbAdapter);
            $sQueryStr = $sql->buildSqlString($queryContainer->exportAllFacilityQuery);
            $sResult = $dbAdapter->query($sQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size' => 12,
                ),
                'alignment' => array(
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => Border::BORDER_THICK,
                    ),
                )
            );
            $borderStyle = array(
                'alignment' => array(
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                ),
                'borders' => array(
                    'outline' => array(
                        'style' => Border::BORDER_MEDIUM,
                    ),
                )
            );

            $sheet->mergeCells('A1:B1');
            $sheet->mergeCells('A2:B2');
            $sheet->mergeCells('A4:A5');
            $sheet->mergeCells('B4:B5');
            $sheet->mergeCells('C4:C5');
            $sheet->mergeCells('D4:D5');

            $sheet->setCellValue('A1', html_entity_decode('Facility Report', ENT_QUOTES, 'UTF-8'));

            $sheet->setCellValue('A4', html_entity_decode('Facility Id', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('B4', html_entity_decode('Facility Name', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('C4', html_entity_decode('Email', ENT_QUOTES, 'UTF-8'));
            $sheet->setCellValue('D4', html_entity_decode('Contact Person', ENT_QUOTES, 'UTF-8'));

            $sheet->getStyle('A1:B1')->getFont()->setBold(TRUE)->setSize(16);

            $sheet->getStyle('A4:A5')->applyFromArray($styleArray);
            $sheet->getStyle('B4:B5')->applyFromArray($styleArray);
            $sheet->getStyle('C4:C5')->applyFromArray($styleArray);
            $sheet->getStyle('D4:D5')->applyFromArray($styleArray);
            $output = array();
            if (count($sResult) > 0) {
                foreach ($sResult as $aRow) {
                    $row = array();
                    $row[] = $aRow['facility_id'];
                    $row[] = $aRow['facility_name'];
                    $row[] = $aRow['email'];
                    $row[] = $aRow['contact_person'];
                    $output[] = $row;
                }
            }
            foreach ($output as $rowNo => $rowData) {
                $colNo = 1;
                $rRowCount = $rowNo + 6;
                foreach ($rowData as $field => $value) {
                    if (!isset($value)) {
                        $value = "";
                    }
                    if (is_numeric($value)) {
                        $sheet->setCellValue(Coordinate::stringFromColumnIndex($colNo) . $rRowCount, html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
                    } else {
                        $sheet->setCellValue(Coordinate::stringFromColumnIndex($colNo) . $rRowCount, html_entity_decode((string) $value));
                    }
                    $cellName = Coordinate::stringFromColumnIndex($colNo) . $rRowCount;
                    $sheet->getStyle($cellName . $rRowCount)->applyFromArray($borderStyle);
                    $sheet->getDefaultRowDimension()->setRowHeight(18);
                    $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                    $sheet->getStyle($cellName)->getAlignment()->setWrapText(true);
                    $colNo++;
                }
            }
            $writer = IOFactory::createWriter($excel, IOFactory::READER_XLSX);
            $filename = 'facility-list-report-' . date('d-M-Y-H-i-s') . '.xls';
            $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . $filename);
            return $filename;
        } catch (Exception $exc) {
            return "";
            error_log("GENERATE-FACILITY-REPORT-EXCEL--" . $exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }
    public function getProvinceData($searchStr)
    {
        $facilityDb = $this->sm->get('SpiRtFacilitiesTable');
        return $facilityDb->fecthProvinceData($searchStr);
    }
    public function getDistrictData($searchStr)
    {
        $facilityDb = $this->sm->get('SpiRtFacilitiesTable');
        return $facilityDb->fecthDistrictData($searchStr);
    }

    public function getFacilityDetails($params)
    {
        $facilityDb = $this->sm->get('SpiRtFacilitiesTable');
        return $facilityDb->fetchFacilityDetails($params);
    }
}
