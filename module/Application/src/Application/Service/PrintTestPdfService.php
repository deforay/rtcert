<?php
namespace Application\Service;

use Laminas\Db\Sql\Sql;
use Laminas\Db\Sql\Expression;
use Laminas\Db\Adapter\Adapter;
use Laminas\Session\Container;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class PrintTestPdfService {

    public $sm = null;

    public function __construct($sm) {
        $this->sm = $sm;
    }

    public function getServiceManager() {
        return $this->sm;
    }

    public function getprintTestPdfGrid($params){
        $db = $this->sm->get('PrintTestPdfTable');
        $acl = $this->sm->get('AppAcl');
        return $db->fetchprintTestPdfList($params,$acl);
    }
    
    public function getPtpDetailsInGrid($params){
        $db = $this->sm->get('PrintTestPdfTable');
        $acl = $this->sm->get('AppAcl');
        return $db->fetchPtpDetailsInGrid($params,$acl);
    }

    public function addPrintTestPdfData($params)
    {
        $adapter = $this->sm->get('Laminas\Db\Adapter\Adapter')->getDriver()->getConnection();
        $adapter->beginTransaction();
        try {
            $db = $this->sm->get('PrintTestPdfTable');
            $result = $db->savePrintTestPdfData($params);
            if ($result > 0) {
                $adapter->commit();
                $alertContainer = new Container('alert');
                $alertContainer->alertMsg = 'New test details added successfully';
                // Add event log
                $subject                = $result;
                $eventType              = 'New test details-add';
                $action                 = 'Added a new test details';
                $resourceName           = 'Print Test PDF Details';
                $eventLogDb             = $this->sm->get('EventLogTable');
                $eventLogDb->addEventLog($subject, $eventType, $action, $resourceName);
            }
        } catch (Exception $exc) {
            $adapter->rollBack();
            error_log($exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }

    public function getPtpDetailsById($ptpId){
        $db = $this->sm->get('PrintTestPdfTable');
        return $db->fetchPtpDetailsById($ptpId);
    }
    
    public function getAllprintTestPdf(){
        $db = $this->sm->get('PrintTestPdfTable');
        return $db->fetchAllprintTestPdf();
    }
    
    public function getPdfDetailsById($ptpId,$answer=''){
        $db = $this->sm->get('PrintTestPdfTable');
        return $db->fetchPdfDetailsById($ptpId,$answer);
    }
    
    public function getPrintTestPdfDetailsById($ptpId){
        $db = $this->sm->get('PrintTestPdfTable');
        return $db->fetchPrintTestPdfDetailsById($ptpId);
    }
    
    public function savePdfTitle($params){
        $db = $this->sm->get('PrintTestPdfTable');
        return $db->savePdfTitleData($params);
    }
    
    public function changeStatus($params){
        $db = $this->sm->get('PrintTestPdfTable');
        return $db->saveChangedStatus($params);
    }

    public function exportPdfDataDetails(){
        try{
            $querycontainer = new Container('query');
            $excel = new Spreadsheet();
            $sheet = $excel->getActiveSheet();
            $dbAdapter = $this->sm->get('Laminas\Db\Adapter\Adapter');
            $sResult = $dbAdapter->query($querycontainer->printTestPdfQueryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();
            $output = array();
            if(count($sResult) > 0) {
                foreach($sResult as $aRow) {
                    $row = array();
                    $row[] = ucwords($aRow['ptp_title']);
                    $row[] = $aRow['ptp_no_participants'];
                    $row[] = $aRow['ptp_variation'];
                    $row[] = ucwords($aRow['first_name'].' '.$aRow['last_name']);
                    $row[] = date('d-M-Y h:i A',strtotime($aRow['ptp_create_on']));
                    $output[] = $row;
                }
                $styleArray = array(
                    'font' => array(
                        'bold' => true,
                        'size'=>12,
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
                
                $sheet->setCellValue('A1', html_entity_decode('Title', ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue('B1', html_entity_decode('Number of Participants', ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue('C1', html_entity_decode('Number of Variants', ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue('D1', html_entity_decode('Test Created By', ENT_QUOTES, 'UTF-8'));
                $sheet->setCellValue('E1', html_entity_decode('Created On', ENT_QUOTES, 'UTF-8'));

                $sheet->getStyle('A1:E1')->applyFromArray($styleArray);

                foreach ($output as $rowNo => $rowData) {
                    $colNo = 1;
                    $rRowCount = $rowNo + 2;
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
                        $sheet->getDefaultRowDimension()->setRowHeight(18);
                        $sheet->getColumnDimensionByColumn($colNo)->setWidth(20);
                        $sheet->getStyle($cellName)->getAlignment()->setWrapText(true);
                        $colNo++;
                    }
                }

                $writer = IOFactory::createWriter($excel, IOFactory::READER_XLSX);
                $filename = 'print-test-pdf-report-(' . date('d-M-Y_H:i_a') . ').xlsx';
                $directoryName = TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'print-test-pdf';

                if(!is_dir($directoryName)){
                    mkdir($directoryName, 0755);
                    $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'print-test-pdf' . DIRECTORY_SEPARATOR . $filename);

                }else{
                    chmod($directoryName, 0777);
                    $writer->save(TEMP_UPLOAD_PATH . DIRECTORY_SEPARATOR . 'print-test-pdf' . DIRECTORY_SEPARATOR . $filename);
                }
                return $filename;
            }else{
                return "not-found";
            }
        }
        catch (Exception $exc) {
            return "";
            error_log("EXPORT-PRINT-TEST-PDF-REPORT-EXCEL--" . $exc->getMessage());
            error_log($exc->getTraceAsString());
        }
    }
}