<?php

namespace Certification\Controller;

use Laminas\Session\container;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Certification\Model\Training;
use Certification\Form\TrainingForm;
use Laminas\Json\Json;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class TrainingController extends AbstractActionController
{

    public \Certification\Model\TrainingTable $trainingTable;
    public \Certification\Form\TrainingForm $trainingForm;

    public function __construct($trainingTable, $trainingForm)
    {
        $this->trainingTable = $trainingTable;
        $this->trainingForm = $trainingForm;
    }

    public function indexAction()
    {

        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->trainingTable->fetchAllTraining($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
        // return new ViewModel(array(
        //     'trainings' => $this->trainingTable->fetchAllTraining(),
        // ));

    }

    public function addAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        $this->trainingForm->get('submit')->setValue('SUBMIT');

        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();

            $training = new Training();
            $this->trainingForm->setInputFilter($training->getInputFilter());
            $select_time = $request->getPost('select_time');
            //die($select_time);
            //$this->trainingForm->setData($request->getPost());
            //if ($this->trainingForm->isValid()) {
            $training->exchangeArray($request->getPost());
            $training->length_of_training = $training->length_of_training . ' ' . $select_time;
            $this->trainingTable->saveTraining($training);
            $container = new Container('alert');
            $container->alertMsg = 'New training added successfully';

            return $this->redirect()->toRoute('training', array('action' => 'add'));
            //}
        }

        return array('form' => $this->trainingForm);
    }

    //
    public function editAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));

        $training_id = (int) base64_decode($this->params()->fromRoute('training_id', 0));
        if ($training_id === 0) {
            return $this->redirect()->toRoute('training', array(
                'action' => 'add'
            ));
        }

        try {
            /** @var \Laminas\Http\Request $request */
            $request = $this->getRequest();
            if ($request->isPost()) {
                $training = new Training();
                $this->trainingForm->setInputFilter($training->getInputFilter());
                $select_time = $request->getPost('select_time');

                $training->exchangeArray($request->getPost());
                $training->length_of_training = $training->length_of_training . ' ' . $select_time;
                $this->trainingTable->saveTraining($training);
                $container = new Container('alert');
                $container->alertMsg = 'Training updated successfully';
                return $this->redirect()->toRoute('training');
            }
            $training = $this->trainingTable->getTraining($training_id);
        } catch (\Exception $ex) {
            return $this->redirect()->toRoute('training', array(
                'action' => 'index'
            ));
        }
        if (isset($training->last_training_date)) {
            $training->last_training_date = date("d-m-Y", strtotime($training->last_training_date));
        }
        if (isset($training->date_certificate_issued)) {
            $training->date_certificate_issued = date("d-m-Y", strtotime($training->date_certificate_issued));
        }

        $time_array = explode(' ', $training->length_of_training);
        $time1 = $time_array[0];
        $time2 = $time_array[1];

        $training->length_of_training = $time1;
        $this->trainingForm->bind($training);
        $this->trainingForm->get('submit')->setAttribute('value', 'UPDATE');

        return array(
            'training_id' => $training_id,
            'form' => $this->trainingForm,
            'time2' => $time2,
        );
    }

    public function deleteAction()
    {
        $training_id = (int) $this->params()->fromRoute('training_id', 0);

        if ($training_id === 0) {
            return $this->redirect()->toRoute('training');
        } else {

            $this->trainingTable->deleteTraining($training_id);
            $container = new Container('alert');
            $container->alertMsg = 'Deleted successfully';
            return $this->redirect()->toRoute('training');
        }
    }

    public function xlsAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        $this->trainingForm->get('submit')->setValue('GET REPORT');
        $countries = $this->trainingTable->getAllActiveCountries();
        /** @var \Laminas\Http\Request $request */
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
            $sResult = $this->trainingTable->report($type_of_competency, $type_of_training, $training_organization_id, $training_certificate, $typeHiv, $jobTitle, $country, $region, $district, $facility);
            $excel = new Spreadsheet();
            $sheet = $excel->getActiveSheet();
            $sheet->mergeCells('A1:F1'); //merge some column
            $sheet->mergeCells('G1:M1');
            $sheet->mergeCells('Q1:S1');
            $sheet->mergeCells('T1:V1');
            $sheet->mergeCells('W1:AF1');

            $sheet->setCellValue('A1', 'Tester Identification');
            $sheet->SetCellValue('G1', 'Tester Contact Information');
            $sheet->SetCellValue('Q1', 'Testing Site In charge');
            $sheet->SetCellValue('T1', 'Facility In Charge');
            $sheet->SetCellValue('W1', 'Training');

            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size' => 11,
                    'name' => 'Verdana',
                )
            );
            $sheet->getStyle('A1:AF2')->applyFromArray($styleArray); //apply style from array style array
            $sheet->getStyle('A1:AF2')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK); // set cell border

            $sheet->getRowDimension(1)->setRowHeight(17); // row dimension
            $sheet->getRowDimension(2)->setRowHeight(30);

            $sheet->getDefaultColumnDimension()->setWidth(25);

            $sheet->getStyle('A1:F2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFF8DC'); //column fill
            $sheet->getStyle('G1:M2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6FA');
            $sheet->getStyle('N1:N2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F5DEB3');
            $sheet->getStyle('Q1:S2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('A9A9A9');
            $sheet->getStyle('T1:V2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('7FFFD4');
            $sheet->getStyle('W1:AF2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F5F5DC');


            $sheet->SetCellValue('A2', 'Certification registration no');
            $sheet->SetCellValue('B2', 'Certification id');
            $sheet->SetCellValue('C2', 'Professional registration no');
            $sheet->SetCellValue('D2', 'Last name');
            $sheet->SetCellValue('E2', 'First name');
            $sheet->SetCellValue('F2', 'Middle name');
            $sheet->SetCellValue('G2', 'Country');
            $sheet->SetCellValue('H2', 'Region');
            $sheet->SetCellValue('I2', 'District');
            $sheet->SetCellValue('J2', 'Type of vih test');
            $sheet->SetCellValue('K2', 'Phone');
            $sheet->SetCellValue('L2', 'Email');
            $sheet->SetCellValue('M2', 'Prefered contact method');
            $sheet->SetCellValue('N2', 'Facility');
            $sheet->SetCellValue('O2', 'Current job title');
            $sheet->SetCellValue('P2', 'Time worked as tester');
            $sheet->SetCellValue('Q2', 'Testing site in charge name');
            $sheet->SetCellValue('R2', 'Testing site in charge phone');
            $sheet->SetCellValue('S2', 'Testing site in charge email');
            $sheet->SetCellValue('T2', 'Facility in charge name');
            $sheet->SetCellValue('U2', 'Facility in charge phone');
            $sheet->SetCellValue('V2', 'Facility in charge email');
            $sheet->SetCellValue('W2', 'Type of competency');
            $sheet->SetCellValue('X2', 'Date of last training/activity');
            $sheet->SetCellValue('Y2', 'Type of activity/training');
            $sheet->SetCellValue('Z2', 'Length of training/activity');
            $sheet->SetCellValue('AA2', 'Training organization');
            $sheet->SetCellValue('AB2', 'Type of training organization');
            $sheet->SetCellValue('AC2', 'Name of facilitator(s)');
            $sheet->SetCellValue('AD2', 'Training certificate (if available)');
            $sheet->SetCellValue('AE2', 'Date certificate issued (if available)');
            $sheet->SetCellValue('AF2', 'Comments');

            $output = array();
            foreach($sResult as $aRow) {
                $row = array();
                $row[] = $aRow['certification_reg_no'];
                $row[] = $aRow['certification_id'];
                $row[] = $aRow['professional_reg_no'];
                $row[] = (isset($excludeTesterName) && $excludeTesterName == 'yes') ? $aRow['last_name'] : '';
                $row[] = (isset($excludeTesterName) && $excludeTesterName == 'yes') ? $aRow['first_name'] : '';
                $row[] = (isset($excludeTesterName) && $excludeTesterName == 'yes') ? $aRow['middle_name'] : '';
                $row[] = isset($aRow['country_name']) ? $aRow['country_name'] : '';
                $row[] = isset($aRow['region_name']) ? $aRow['region_name'] : '';
                $row[] = isset($aRow['district_name']) ? $aRow['district_name'] : '';
                $row[] = $aRow['type_vih_test'];
                $row[] = $aRow['phone'];
                $row[] = $aRow['email'];
                $row[] = $aRow['prefered_contact_method'];
                $row[] = $aRow['facility_name'];
                $row[] = $aRow['current_jod'];
                $row[] = $aRow['time_worked'];
                $row[] = $aRow['test_site_in_charge_name'];
                $row[] = $aRow['test_site_in_charge_phone'];
                $row[] = $aRow['test_site_in_charge_email'];
                $row[] = $aRow['facility_in_charge_name'];
                $row[] = $aRow['facility_in_charge_phone'];
                $row[] = $aRow['facility_in_charge_email'];
                $row[] = $aRow['type_of_competency'];
                $row[] = isset($aRow['last_training_date']) ? date("d-m-Y", strtotime($aRow['last_training_date'])) : '';
                $row[] = $aRow['type_of_training'];
                $row[] = $aRow['length_of_training'];
                $row[] = $aRow['training_organization_name'];
                $row[] = $aRow['type_organization'];
                $row[] = $aRow['facilitator'];
                $row[] = $aRow['training_certificate'];
                $row[] = isset($aRow['date_certificate_issued']) ? date("d-m-Y", strtotime($aRow['date_certificate_issued'])) : '';
                $row[] = $aRow['Comments'];
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

            $sheet->getStyle('A2:AF2')->getAlignment()->setWrapText(true); // make a new line in cell
            $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);  //center column contain

            $writer = IOFactory::createWriter($excel, IOFactory::READER_XLSX);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . date('d-m-Y') . '_trainingReport.xlsx"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            exit;
        }
        return array(
            'form' => $this->trainingForm,
            'countries' => $countries
        );
        //    }
    }

    public function getTrainingReportsAction()
    {
        /** @var \Laminas\Http\Request $request */
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
        $training = $this->trainingTable->report($type_of_competency, $type_of_training, $training_organization_id, $training_certificate, $typeHiv, $jobTitle, $country, $region, $district, $facility);
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $training));
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    public function getTrainingReportAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $parameters['addproviders'] = "addproviders";
            $result = $this->trainingTable->reportData($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
}
