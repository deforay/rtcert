<?php

namespace Certification\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Certification\Model\Examination;
use Certification\Form\ExaminationForm;
use Zend\Json\Json;
use Zend\View\Model\ViewModel;

class ExaminationController extends AbstractActionController {

    protected $examinationTable;

    public function getExaminationTable() {
        if (!$this->examinationTable) {
            $sm = $this->getServiceLocator();
            $this->examinationTable = $sm->get('Certification\Model\ExaminationTable');
        }
        return $this->examinationTable;
    }

    public function indexAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->getExaminationTable()->fetchAll($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
        return new ViewModel(array());
    }
    
    public function recommendedAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->getExaminationTable()->fetchAllRecommended($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
    
    public function approvedAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->getExaminationTable()->fetchAllApproved($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
    
    public function pendingAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->getExaminationTable()->fetchAllPendingTests($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
        return new ViewModel(array()); 
    }
    
    public function failedAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->getExaminationTable()->fetchAllFailedTests($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        } 
    }

    public function xlsAction() {
        $this->forward()->dispatch('Certification\Controller\Certification', array('action' => 'index'));
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            // print_r($params['Exam']);die;
            $examination = $this->getExaminationTable()->report($params);
            $objPHPExcel = new \PHPExcel();
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->setActiveSheetIndex()->mergeCells('A1:D1'); //merge some column
            $objPHPExcel->setActiveSheetIndex()->mergeCells('E1:I1');
            if($params['Exam'] == 'online-exam'){
                $objPHPExcel->setActiveSheetIndex()->mergeCells('K1:R1');
            }
            else{
                $objPHPExcel->setActiveSheetIndex()->mergeCells('K1:N1');
            }
            // $objPHPExcel->setActiveSheetIndex()->mergeCells('T1:V1');

            $objPHPExcel->getActiveSheet()->setCellValue('A1', 'Tester Identification');
            $objPHPExcel->getActiveSheet()->SetCellValue('E1', 'Tester Contact Information');
            $objPHPExcel->getActiveSheet()->SetCellValue('K1', 'Test Details');
            // $objPHPExcel->getActiveSheet()->SetCellValue('T1', 'Facility In Charge');

            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size' => 11,
                    'name' => 'Verdana',
            ));
            if($params['Exam'] == 'online-exam'){
                $objPHPExcel->getActiveSheet()->getStyle('A1:R2')->applyFromArray($styleArray); //apply style from array style array
                $objPHPExcel->getActiveSheet()->getStyle('A1:R2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THICK); // set cell border
            }
            else{
                $objPHPExcel->getActiveSheet()->getStyle('A1:N2')->applyFromArray($styleArray); //apply style from array style array
                $objPHPExcel->getActiveSheet()->getStyle('A1:N2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THICK); // set cell border
            }
            $objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(17); // row dimension
            $objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(30);

            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(25);

            $objPHPExcel->getActiveSheet()->getStyle('A1:E2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FFF8DC'); //column fill
            $objPHPExcel->getActiveSheet()->getStyle('E1:I2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6FA');
            // $objPHPExcel->getActiveSheet()->getStyle('N1:N2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('F5DEB3');
            if($params['Exam'] == 'online-exam'){
                $objPHPExcel->getActiveSheet()->getStyle('K1:R2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('A9A9A9');
            }
            else{
                $objPHPExcel->getActiveSheet()->getStyle('K1:N2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('A9A9A9');
            }
            // $objPHPExcel->getActiveSheet()->getStyle('T1:V2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('7FFFD4');

            $objPHPExcel->getActiveSheet()->SetCellValue('A2', 'Professional registration no');
            $objPHPExcel->getActiveSheet()->SetCellValue('B2', 'Last name');
            $objPHPExcel->getActiveSheet()->SetCellValue('C2', 'First name');
            $objPHPExcel->getActiveSheet()->SetCellValue('D2', 'Middle name');
            $objPHPExcel->getActiveSheet()->SetCellValue('E2', 'Country');
            $objPHPExcel->getActiveSheet()->SetCellValue('F2', 'Region');
            $objPHPExcel->getActiveSheet()->SetCellValue('G2', 'District');
            $objPHPExcel->getActiveSheet()->SetCellValue('H2', 'Phone');
            $objPHPExcel->getActiveSheet()->SetCellValue('I2', 'Email');
            $objPHPExcel->getActiveSheet()->SetCellValue('J2', 'Facility');
            if($params['Exam'] == 'online-exam'){
                $objPHPExcel->getActiveSheet()->SetCellValue('K2', 'Pre test start date');
                $objPHPExcel->getActiveSheet()->SetCellValue('L2', 'Pre test end date');
                $objPHPExcel->getActiveSheet()->SetCellValue('M2', 'Pre test score');
                $objPHPExcel->getActiveSheet()->SetCellValue('N2', 'Pre test status');
                $objPHPExcel->getActiveSheet()->SetCellValue('O2', 'Post test start date');
                $objPHPExcel->getActiveSheet()->SetCellValue('P2', 'Post test end date');
                $objPHPExcel->getActiveSheet()->SetCellValue('Q2', 'Post test score');
                $objPHPExcel->getActiveSheet()->SetCellValue('R2', 'Post test status');
            }
            else{
                $objPHPExcel->getActiveSheet()->SetCellValue('K2', 'Practical exam date');
                $objPHPExcel->getActiveSheet()->SetCellValue('L2', 'Practical total score');
                $objPHPExcel->getActiveSheet()->SetCellValue('M2', 'Written exam date');
                $objPHPExcel->getActiveSheet()->SetCellValue('N2', 'Final score');
            }


            $ligne = 3;
            foreach ($examination as $examination) {

                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1, $ligne, $examination['professional_reg_no']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(2, $ligne, (isset($excludeTesterName) && $excludeTesterName == 'yes')?$examination['last_name']:'');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(3, $ligne, (isset($excludeTesterName) && $excludeTesterName == 'yes')?$examination['first_name']:'');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(4, $ligne, (isset($excludeTesterName) && $excludeTesterName == 'yes')?$examination['middle_name']:'');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(5, $ligne, (isset($examination['country_name']))?$examination['country_name']:'');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(6, $ligne, (isset($examination['region_name']))?$examination['region_name']:'');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(7, $ligne, (isset($examination['district_name']))?$examination['district_name']:'');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(8, $ligne, $examination['phone']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(9, $ligne, $examination['email']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(10, $ligne, $examination['facility_name']);
                if($params['Exam'] == 'online-exam'){
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(11, $ligne, (isset($examination['pretest_start_datetime']) && $examination['pretest_start_datetime'] != NULL)? date("d-m-Y", strtotime($examination['pretest_start_datetime'])):'');
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(12, $ligne, (isset($examination['pretest_end_datetime']) && $examination['pretest_end_datetime'] != NULL)? date("d-m-Y", strtotime($examination['pretest_end_datetime'])):'');
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(13, $ligne, $examination['pre_test_score']);
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(14, $ligne, $examination['pre_test_status']);
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(15, $ligne, (isset($examination['posttest_start_datetime']) && $examination['posttest_start_datetime'] != NULL)? date("d-m-Y", strtotime($examination['posttest_start_datetime'])):'');
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(16, $ligne, (isset($examination['posttest_end_datetime']) && $examination['posttest_end_datetime'] != NULL)? date("d-m-Y", strtotime($examination['posttest_end_datetime'])):'');
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(17, $ligne, $examination['post_test_score']);
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(18, $ligne, $examination['post_test_status']);
                }
                else{
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(11, $ligne, (isset($examination['practicalExamDate']) && $examination['practicalExamDate'] != NULL)? date("d-m-Y", strtotime($examination['practicalExamDate'])):'');
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(12, $ligne, $examination['practical_total_score']);
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(13, $ligne, (isset($examination['writenExamDate']) && $examination['writenExamDate'] != NULL)? date("d-m-Y", strtotime($examination['writenExamDate'])):'');
                    $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(14, $ligne, $examination['final_score']);
                }
                $ligne++;
            }
            $objPHPExcel->getActiveSheet()->getStyle('A2:U2')->getAlignment()->setWrapText(true); // make a new line in cell
            $objPHPExcel->getActiveSheet()->getStyle($objPHPExcel->getActiveSheet()->calculateWorksheetDimension())->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  //center column contain

            $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . date('d-m-Y') . '_list of all testers.xlsx"');
            header('Cache-Control: max-age=0');
            $objWriter->save('php://output');
            exit;
        }
        $common = $this->getServiceLocator()->get('CommonService');
        return array('country' => $common->getAllActiveCountries());
    }

    public function getReportAction()
    {
        $request = $this->getRequest();
        if($request->isPost())
        {
            $params = $request->getPost();
            $result = $this->getExaminationTable()->report($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' =>$result, 'params' => $params));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }

}
