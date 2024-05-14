<?php

namespace Certification\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Json\Json;
use Laminas\View\Model\ViewModel;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ExaminationController extends AbstractActionController
{

    public \Certification\Model\ExaminationTable $examinationTable;
    public \Application\Service\CommonService $commonService;

    public function __construct($commonService, $examinationTable)
    {
        $this->commonService = $commonService;
        $this->examinationTable = $examinationTable;
    }

    public function indexAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->examinationTable->fetchAll($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
        return new ViewModel(array());
    }

    public function recommendedAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->examinationTable->fetchAllRecommended($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function approvedAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->examinationTable->fetchAllApproved($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function pendingAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->examinationTable->fetchAllPendingTests($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
        return new ViewModel(array());
    }

    public function failedAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->examinationTable->fetchAllFailedTests($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function xlsAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $excludeTesterName = $request->getPost('exclude_tester_name');
            $params = $request->getPost();
            $sResult = $this->examinationTable->report($params);
            $excel = new Spreadsheet();
            $sheet = $excel->getActiveSheet();
            $sheet->mergeCells('A1:D1'); //merge some column
            $sheet->mergeCells('E1:I1');
            if ($params['Exam'] == 'online-exam') {
                $sheet->mergeCells('K1:R1');
            } else {
                $sheet->mergeCells('K1:N1');
            }

            $sheet->setCellValue('A1', 'Tester Identification');
            $sheet->SetCellValue('E1', 'Tester Contact Information');
            $sheet->SetCellValue('K1', 'Test Details');

            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size' => 11,
                    'name' => 'Verdana',
                )
            );
            if ($params['Exam'] == 'online-exam') {
                $sheet->getStyle('A1:R2')->applyFromArray($styleArray); //apply style from array style array
                $sheet->getStyle('A1:R2')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK); // set cell border
            } else {
                $sheet->getStyle('A1:N2')->applyFromArray($styleArray); //apply style from array style array
                $sheet->getStyle('A1:N2')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK); // set cell border
            }
            $sheet->getRowDimension(1)->setRowHeight(17); // row dimension
            $sheet->getRowDimension(2)->setRowHeight(30);

            $sheet->getDefaultColumnDimension()->setWidth(25);

            $sheet->getStyle('A1:E2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFF8DC'); //column fill
            $sheet->getStyle('E1:I2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6FA');
            if ($params['Exam'] == 'online-exam') {
                $sheet->getStyle('K1:R2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('A9A9A9');
            } else {
                $sheet->getStyle('K1:N2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('A9A9A9');
            }

            $sheet->SetCellValue('A2', 'Professional registration no');
            $sheet->SetCellValue('B2', 'First name');
            $sheet->SetCellValue('C2', 'Middle name');
            $sheet->SetCellValue('D2', 'Last name');
            $sheet->SetCellValue('E2', 'Country');
            $sheet->SetCellValue('F2', 'Region');
            $sheet->SetCellValue('G2', 'District');
            $sheet->SetCellValue('H2', 'Phone');
            $sheet->SetCellValue('I2', 'Email');
            $sheet->SetCellValue('J2', 'Facility');
            if ($params['Exam'] == 'online-exam') {
                $sheet->SetCellValue('K2', 'Pre test start date');
                $sheet->SetCellValue('L2', 'Pre test end date');
                $sheet->SetCellValue('M2', 'Pre test score');
                $sheet->SetCellValue('N2', 'Pre test status');
                $sheet->SetCellValue('O2', 'Post test start date');
                $sheet->SetCellValue('P2', 'Post test end date');
                $sheet->SetCellValue('Q2', 'Post test score');
                $sheet->SetCellValue('R2', 'Post test status');
            } else {
                $sheet->SetCellValue('K2', 'Practical exam date');
                $sheet->SetCellValue('L2', 'Practical total score');
                $sheet->SetCellValue('M2', 'Written exam date');
                $sheet->SetCellValue('N2', 'Final score');
            }

            $output = array();
            foreach($sResult as $aRow) {
                $row = array();
                $row[] = $aRow['professional_reg_no'];
                $row[] = (isset($excludeTesterName) && $excludeTesterName == 'yes') ? $aRow['first_name'] : '';
                $row[] = (isset($excludeTesterName) && $excludeTesterName == 'yes') ? $aRow['middle_name'] : '';
                $row[] = (isset($excludeTesterName) && $excludeTesterName == 'yes') ? $aRow['last_name'] : '';
                $row[] = (isset($aRow['country_name'])) ? $aRow['country_name'] : '';
                $row[] = (isset($aRow['regionName'])) ? $aRow['regionName'] : '';
                $row[] = (isset($aRow['districtName'])) ? $aRow['districtName'] : '';
                $row[] = $aRow['phone'];
                $row[] = $aRow['email'];
                $row[] = $aRow['facility_name'];
                if ($params['Exam'] == 'online-exam') {
                    $row[] = (isset($aRow['pretest_start_datetime']) && $aRow['pretest_start_datetime'] != NULL) ? date("d-M-Y", strtotime($aRow['pretest_start_datetime'])) : '';
                    $row[] = (isset($aRow['pretest_end_datetime']) && $aRow['pretest_end_datetime'] != NULL) ? date("d-M-Y", strtotime($aRow['pretest_end_datetime'])) : '';
                    $row[] = $aRow['pre_test_score'];
                    $row[] = $aRow['pre_test_status'];
                    $row[] = (isset($aRow['posttest_start_datetime']) && $aRow['posttest_start_datetime'] != NULL) ? date("d-M-Y", strtotime($aRow['posttest_start_datetime'])) : '';
                    $row[] = (isset($aRow['posttest_end_datetime']) && $aRow['posttest_end_datetime'] != NULL) ? date("d-M-Y", strtotime($aRow['posttest_end_datetime'])) : '';
                    $row[] = $aRow['post_test_score'];
                    $row[] = $aRow['post_test_status'];
                } else {
                    $row[] = (isset($aRow['practicalExamDate']) && $aRow['practicalExamDate'] != NULL) ? date("d-M-Y", strtotime($aRow['practicalExamDate'])) : '';
                    $row[] = $aRow['practical_total_score'];
                    $row[] = (isset($aRow['writenExamDate']) && $aRow['writenExamDate'] != NULL) ? date("d-M-Y", strtotime($aRow['writenExamDate'])) : '';
                    $row[] = $aRow['practical_total_score'];
                }
                $output[] = $row;
            }
            foreach ($output as $rowNo => $rowData) {
                $colNo = 1;
                $rRowCount = $rowNo + 3;
                foreach ($rowData as $field => $value) {
                    if (!isset($value)) {
                        $value = "";
                    }
                    if (is_numeric($value)) {
                        $sheet->setCellValue(Coordinate::stringFromColumnIndex($colNo) . $rRowCount, html_entity_decode($value, ENT_QUOTES, 'UTF-8'));
                    } else {
                        $sheet->setCellValue(Coordinate::stringFromColumnIndex($colNo) . $rRowCount, html_entity_decode((string) $value));
                    }
                    $colNo++;
                }
            }
            $sheet->getStyle('A2:U2')->getAlignment()->setWrapText(true); // make a new line in cell
            $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);  //center column contain

            $writer = IOFactory::createWriter($excel, IOFactory::READER_XLSX);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . date('d-m-Y') . '_list of all testers.xlsx"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            exit;
        }
        return array('country' => $this->commonService->getAllActiveCountries());
    }

    public function getReportAction()
    {
        // $request = $this->getRequest();
        // if($request->isPost())
        // {
        //     $params = $request->getPost();
        //     $result = $this->examinationTable->report($params);
        //     $viewModel = new ViewModel();
        //     $viewModel->setVariables(array('result' =>$result, 'params' => $params));
        //     $viewModel->setTerminal(true);
        //     return $viewModel;
        // }

        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->examinationTable->examReportData($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
}
