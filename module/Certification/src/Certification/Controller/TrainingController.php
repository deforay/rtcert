<?php

namespace Certification\Controller;

use Zend\Session\container;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Certification\Model\Training;
use Certification\Form\TrainingForm;

class TrainingController extends AbstractActionController {

    protected $TrainingTable;

    public function getTrainingTable() {
        if (!$this->TrainingTable) {
            $sm = $this->getServiceLocator();
            $this->TrainingTable = $sm->get('Certification\Model\TrainingTable');
        }
        return $this->TrainingTable;
    }

    public function indexAction() {

        $this->forward()->dispatch('Certification\Controller\Certification', array('action' => 'index'));
        return new ViewModel(array(
            'trainings' => $this->getTrainingTable()->fetchAll(),
        ));
    }

    public function addAction() {
        $this->forward()->dispatch('Certification\Controller\Certification', array('action' => 'index'));
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $form = new TrainingForm($dbAdapter);
        $form->get('submit')->setValue('SUBMIT');

        $request = $this->getRequest();
        if ($request->isPost()) {
             $params = $request->getPost();
            
            $training = new Training();
            $form->setInputFilter($training->getInputFilter());
            $select_time = $request->getPost('select_time');
            //die($select_time);
            //$form->setData($request->getPost());
            //if ($form->isValid()) {
                $training->exchangeArray($request->getPost());
                $training->length_of_training = $training->length_of_training . ' ' . $select_time;
                $this->getTrainingTable()->saveTraining($training);
                $container = new Container('alert');
                $container->alertMsg = 'New training added successfully';

                return $this->redirect()->toRoute('training', array('action' => 'add'));
            //}
        }

        return array('form' => $form);
    }

//
    public function editAction() {
        $this->forward()->dispatch('Certification\Controller\Certification', array('action' => 'index'));

        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $training_id = (int) base64_decode($this->params()->fromRoute('training_id', 0));
        if (!$training_id) {
            return $this->redirect()->toRoute('training', array(
                        'action' => 'add'
            ));
        }

        try {
            $request = $this->getRequest();
            $form = new TrainingForm($dbAdapter);
            if ($request->isPost()) {
                $training = new Training();
                $form->setInputFilter($training->getInputFilter());
                $select_time = $request->getPost('select_time');
                
                $training->exchangeArray($request->getPost());
                $training->length_of_training = $training->length_of_training . ' ' . $select_time;
                $this->getTrainingTable()->saveTraining($training);
                $container = new Container('alert');
                $container->alertMsg = 'Training updated successfully';
                return $this->redirect()->toRoute('training');
            }
            $training = $this->getTrainingTable()->getTraining($training_id);
        } catch (\Exception $ex) {
            return $this->redirect()->toRoute('training', array(
                        'action' => 'index'
            ));
        }
        //$training->last_training_date = date("d-m-Y", strtotime($training->last_training_date));
        if (isset($training->date_certificate_issued)) {
            $training->date_certificate_issued = date("d-m-Y", strtotime($training->date_certificate_issued));
        }

        $time_array = explode(' ', $training->length_of_training);
        $time1 = $time_array[0];
        $time2 = $time_array[1];
        
        $training->length_of_training = $time1;
        $form->bind($training);
        $form->get('submit')->setAttribute('value', 'UPDATE');

        return array(
            'training_id' => $training_id,
            'form' => $form,
            'time2' => $time2,
        );
    }

    public function deleteAction() {
        $training_id = (int) $this->params()->fromRoute('training_id', 0);

        if (!$training_id) {
            return $this->redirect()->toRoute('training');
        } else {

            $this->getTrainingTable()->deleteTraining($training_id);
            $container = new Container('alert');
            $container->alertMsg = 'Deleted successfully';
            return $this->redirect()->toRoute('training');
        }
    }

    public function xlsAction() {
        $this->forward()->dispatch('Certification\Controller\Certification', array('action' => 'index'));
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $form = new TrainingForm($dbAdapter);
        $form->get('submit')->setValue('GET REPORT');
        $countries = $this->getTrainingTable()->getAllActiveCountries();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $type_of_competency = $request->getPost('type_of_competency');
            $type_of_training = $request->getPost('type_of_training');
            $training_organization_id = $request->getPost('training_organization_id');
            $training_certificate = $request->getPost('training_certificate');
            $typeHiv = $request->getPost('type_vih_test');
            $jobTitle = $request->getPost('current_jod');
            $country = $request->getPost('country');
            $region = $request->getPost('region');
            $district = $request->getPost('district');
            $facility = $request->getPost('facility_id');
            $excludeTesterName = $request->getPost('exclude_tester_name');
            $training = $this->getTrainingTable()->report($type_of_competency, $type_of_training, $training_organization_id, $training_certificate, $typeHiv, $jobTitle, $country, $region, $district, $facility);

            $objPHPExcel = new \PHPExcel();
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->setActiveSheetIndex()->mergeCells('A1:F1'); //merge some column
            $objPHPExcel->setActiveSheetIndex()->mergeCells('G1:M1');
            $objPHPExcel->setActiveSheetIndex()->mergeCells('Q1:S1');
            $objPHPExcel->setActiveSheetIndex()->mergeCells('T1:V1');
            $objPHPExcel->setActiveSheetIndex()->mergeCells('W1:AF1');

            $objPHPExcel->getActiveSheet()->setCellValue('A1', 'Tester Identification');
            $objPHPExcel->getActiveSheet()->SetCellValue('G1', 'Tester Contact Information');
            $objPHPExcel->getActiveSheet()->SetCellValue('Q1', 'Testing Site In charge');
            $objPHPExcel->getActiveSheet()->SetCellValue('T1', 'Facility In Charge');
            $objPHPExcel->getActiveSheet()->SetCellValue('W1', 'Training');

            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size' => 11,
                    'name' => 'Verdana',
            ));
            $objPHPExcel->getActiveSheet()->getStyle('A1:AF2')->applyFromArray($styleArray); //apply style from array style array
            $objPHPExcel->getActiveSheet()->getStyle('A1:AF2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THICK); // set cell border

            $objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(17); // row dimension
            $objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(30);

            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(25);

            $objPHPExcel->getActiveSheet()->getStyle('A1:F2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FFF8DC'); //column fill
            $objPHPExcel->getActiveSheet()->getStyle('G1:M2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6FA');
            $objPHPExcel->getActiveSheet()->getStyle('N1:N2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('F5DEB3');
            $objPHPExcel->getActiveSheet()->getStyle('Q1:S2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('A9A9A9');
            $objPHPExcel->getActiveSheet()->getStyle('T1:V2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('7FFFD4');
            $objPHPExcel->getActiveSheet()->getStyle('W1:AF2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('F5F5DC');


            $objPHPExcel->getActiveSheet()->SetCellValue('A2', 'Certification registration no');
            $objPHPExcel->getActiveSheet()->SetCellValue('B2', 'Certification id');
            $objPHPExcel->getActiveSheet()->SetCellValue('C2', 'Professional registration no');
            $objPHPExcel->getActiveSheet()->SetCellValue('D2', 'Last name');
            $objPHPExcel->getActiveSheet()->SetCellValue('E2', 'First name');
            $objPHPExcel->getActiveSheet()->SetCellValue('F2', 'Middle name');
            $objPHPExcel->getActiveSheet()->SetCellValue('G2', 'Country');
            $objPHPExcel->getActiveSheet()->SetCellValue('H2', 'Region');
            $objPHPExcel->getActiveSheet()->SetCellValue('I2', 'District');
            $objPHPExcel->getActiveSheet()->SetCellValue('J2', 'Type of vih test');
            $objPHPExcel->getActiveSheet()->SetCellValue('K2', 'Phone');
            $objPHPExcel->getActiveSheet()->SetCellValue('L2', 'Email');
            $objPHPExcel->getActiveSheet()->SetCellValue('M2', 'Prefered contact method');
            $objPHPExcel->getActiveSheet()->SetCellValue('N2', 'Facility');
            $objPHPExcel->getActiveSheet()->SetCellValue('O2', 'Current job title');
            $objPHPExcel->getActiveSheet()->SetCellValue('P2', 'Time worked as tester');
            $objPHPExcel->getActiveSheet()->SetCellValue('Q2', 'Testing site in charge name');
            $objPHPExcel->getActiveSheet()->SetCellValue('R2', 'Testing site in charge phone');
            $objPHPExcel->getActiveSheet()->SetCellValue('S2', 'Testing site in charge email');
            $objPHPExcel->getActiveSheet()->SetCellValue('T2', 'Facility in charge name');
            $objPHPExcel->getActiveSheet()->SetCellValue('U2', 'Facility in charge phone');
            $objPHPExcel->getActiveSheet()->SetCellValue('V2', 'Facility in charge email');
            $objPHPExcel->getActiveSheet()->SetCellValue('W2', 'Type of competency');
            $objPHPExcel->getActiveSheet()->SetCellValue('X2', 'Date of last training/activity');
            $objPHPExcel->getActiveSheet()->SetCellValue('Y2', 'Type of activity/training');
            $objPHPExcel->getActiveSheet()->SetCellValue('Z2', 'Length of training/activity');
            $objPHPExcel->getActiveSheet()->SetCellValue('AA2', 'Training organization');
            $objPHPExcel->getActiveSheet()->SetCellValue('AB2', 'Type of training organization');
            $objPHPExcel->getActiveSheet()->SetCellValue('AC2', 'Name of facilitator(s)');
            $objPHPExcel->getActiveSheet()->SetCellValue('AD2', 'Training certificate (if available)');
            $objPHPExcel->getActiveSheet()->SetCellValue('AE2', 'Date certificate issued (if available)');
            $objPHPExcel->getActiveSheet()->SetCellValue('AF2', 'Comments');

            $ligne = 3;
            foreach ($training as $training) {
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, $ligne, $training['certification_reg_no']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1, $ligne, $training['certification_id']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(2, $ligne, $training['professional_reg_no']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(3, $ligne, (isset($excludeTesterName) && $excludeTesterName == 'yes')?$training['last_name']:'');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(4, $ligne, (isset($excludeTesterName) && $excludeTesterName == 'yes')?$training['first_name']:'');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(5, $ligne, (isset($excludeTesterName) && $excludeTesterName == 'yes')?$training['middle_name']:'');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(6, $ligne, (isset($training['country_name']))?$training['country_name']:'');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(7, $ligne, (isset($training['region_name']))?$training['region_name']:'');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(8, $ligne, (isset($training['district_name']))?$training['district_name']:'');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(9, $ligne, $training['type_vih_test']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(10, $ligne, $training['phone']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(11, $ligne, $training['email']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(12, $ligne, $training['prefered_contact_method']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(13, $ligne, $training['facility_name']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(14, $ligne, $training['current_jod']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(15, $ligne, $training['time_worked']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(16, $ligne, $training['test_site_in_charge_name']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(17, $ligne, $training['test_site_in_charge_phone']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(18, $ligne, $training['test_site_in_charge_email']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(19, $ligne, $training['facility_in_charge_name']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(20, $ligne, $training['facility_in_charge_phone']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(21, $ligne, $training['facility_in_charge_email']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(22, $ligne, $training['type_of_competency']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(23, $ligne, date("d-m-Y", strtotime($training['last_training_date'])));
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(24, $ligne, $training['type_of_training']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(25, $ligne, $training['length_of_training']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(26, $ligne, $training['training_organization_name']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(27, $ligne, $training['type_organization']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(28, $ligne, $training['facilitator']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(29, $ligne, $training['training_certificate']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(30, $ligne, date("d-m-Y", strtotime($training['date_certificate_issued'])));
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(31, $ligne, $training['Comments']);
                $ligne++;
            }
            $objPHPExcel->getActiveSheet()->getStyle('A2:AF2')->getAlignment()->setWrapText(true); // make a new line in cell
            $objPHPExcel->getActiveSheet()->getStyle($objPHPExcel->getActiveSheet()->calculateWorksheetDimension())->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  //center column contain

            $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . date('d-m-Y') . '_trainingReport.xlsx"');
            header('Cache-Control: max-age=0');
            $objWriter->save('php://output');
            exit;
        }
        return array(
            'form' => $form,
            'countries' => $countries
            );
//    }
    }

    public function getTrainingReportAction()
    {
        $request = $this->getRequest();
        $type_of_competency = $request->getPost('type_of_competency');
        $type_of_training = $request->getPost('type_of_training');
        $training_organization_id = $request->getPost('training_organization_id');
        $training_certificate = $request->getPost('training_certificate');
        $typeHiv = $request->getPost('type_vih_test');
        $jobTitle = $request->getPost('current_jod');
        $country = $request->getPost('country');
        $region = $request->getPost('region');
        $district = $request->getPost('district');
        $facility = $request->getPost('facility_id');
        $excludeTesterName = $request->getPost('exclude_tester_name');
        $training = $this->getTrainingTable()->report($type_of_competency, $type_of_training, $training_organization_id, $training_certificate, $typeHiv, $jobTitle, $country, $region, $district, $facility);
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' =>$training));
        $viewModel->setTerminal(true);
        return $viewModel;
    }

}
