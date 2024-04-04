<?php

namespace Certification\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Certification\Model\Recertification;
use Certification\Form\RecertificationForm;
use Laminas\Session\Container;
use Laminas\Json\Json;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;   
use PhpOffice\PhpSpreadsheet\IOFactory;

class RecertificationController extends AbstractActionController
{
    public \Certification\Model\RecertificationTable $recertificationTable;
    public \Certification\Form\RecertificationForm $recertificationForm;

    public function __construct($recertificationForm, $recertificationTable)
    {
        $this->recertificationTable = $recertificationTable;
        $this->recertificationForm = $recertificationForm;
    }


    public function indexAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        //        $reminder = $this->recertificationTable->fetchAll2();
        $certification_id = (int) base64_decode($this->params()->fromQuery(base64_encode('certification_id'), null));
        $key = base64_decode($this->params()->fromQuery(base64_encode('key'), null));
        if ($certification_id !== 0 && !empty($key)) {
            $this->recertificationTable->ReminderSent($certification_id);
            $container = new Container('alert');
            $container->alertMsg = 'Perform successfully';
            return $this->redirect()->toRoute('recertification', array(
                'action' => 'add'
            ), array('query' => array(base64_encode('certification_id') => base64_encode($certification_id))));
        } else {

            return new ViewModel(array(
                'recertifications' => $this->recertificationTable->fetchAll(),
                'reminders' => $this->recertificationTable->fetchAll2()
            ));
        }
    }

    public function addAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        $certification_id = (int) base64_decode($this->params()->fromQuery(base64_encode('certification_id'), null));
        
        $this->recertificationForm->get('submit')->setValue('SUBMIT');
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $recertification = new Recertification();
            $this->recertificationForm->setInputFilter($recertification->getInputFilter());
            $this->recertificationForm->setData($request->getPost());

            if ($this->recertificationForm->isValid()) {
                $recertification->exchangeArray($this->recertificationForm->getData());
                $this->recertificationTable->saveRecertification($recertification);
                $container = new Container('alert');
                $container->alertMsg = 'Re-certification added successfully';
                return $this->redirect()->toRoute('recertification', array('action' => 'add'));
            }
        }
        if (isset($certification_id)) {
            $provider = $this->recertificationTable->certificationInfo($certification_id);
            return array(
                'form' => $this->recertificationForm,
                'provider' => $provider
            );
        } else {
            return array('form' => $this->recertificationForm);
        }
    }

    public function editAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        $recertification_id = (int) base64_decode($this->params()->fromRoute('recertification_id', 0));
        if ($recertification_id === 0) {
            return $this->redirect()->toRoute('recertification', array(
                'action' => 'add'
            ));
        }

        try {
            $recertification = $this->recertificationTable->getRecertification($recertification_id);
        } catch (\Exception $ex) {
            return $this->redirect()->toRoute('recertification', array(
                'action' => 'index'
            ));
        }
        $recertification->due_date = date("d-m-Y", strtotime($recertification->due_date));
        if (isset($recertification->date_reminder_sent)) {
            $recertification->date_reminder_sent = date("d-m-Y", strtotime($recertification->date_reminder_sent));
        }
        
        $this->recertificationForm->bind($recertification);
        $this->recertificationForm->get('submit')->setAttribute('value', 'UPDATE');

        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->recertificationForm->setInputFilter($recertification->getInputFilter());
            $this->recertificationForm->setData($request->getPost());

            if ($this->recertificationForm->isValid()) {
                $this->recertificationTable->saveRecertification($recertification);
                $container = new Container('alert');
                $container->alertMsg = 'Re-certification updated successfully';
                return $this->redirect()->toRoute('recertification');
            }
        }

        return array(
            'recertification_id' => $recertification_id,
            'form' => $this->recertificationForm,
        );
    }

    public function xlsAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        $countries = $this->recertificationTable->getAllActiveCountries();
        
        $this->recertificationForm->get('submit')->setValue('DOWNLOAD REPORT');
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $decision = $request->getPost('decision');
            $typeHiv = $request->getPost('typeHIV');
            $jobTitle = $request->getPost('jobTitle');
            $country = $request->getPost('country');
            $region = $request->getPost('region');
            $district = $request->getPost('district');
            $facility = $request->getPost('facility');
            $due_date = $request->getPost('due_date');
            $excludeTesterName = $request->getPost('exclude_tester_name');
            if (!empty($due_date)) {
                $array = explode(" ", $due_date);
                $startDate = date("Y-m-d", strtotime($array[0]));
                $endDate = date("Y-m-d", strtotime($array[2]));
            } else {
                //                
                $startDate = "";
                $endDate = "";
            }
            $reminder_type = $request->getPost('reminder_type');
            $reminder_sent_to = $request->getPost('reminder_sent_to');
            $date_reminder_sent = $request->getPost('date_reminder_sent');
            if (!empty($date_reminder_sent)) {
                $array2 = explode(" ", $date_reminder_sent);
                $startDate2 = date("Y-m-d", strtotime($array2[0]));
                $endDate2 = date("Y-m-d", strtotime($array2[2]));
            } else {
                //                
                $startDate2 = "";
                $endDate2 = "";
            }
            $sResult = $this->recertificationTable->report($startDate, $endDate, $decision, $typeHiv, $jobTitle, $country, $region, $district, $facility, $reminder_type, $reminder_sent_to, $startDate2, $endDate2);
            $excel = new Spreadsheet();
            $sheet = $excel->getActiveSheet();
            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size' => 11,
                    'name' => 'Verdana',
                )
            );

            $sheet->mergeCells('A1:F1'); //merge some column
            $sheet->mergeCells('G1:N1');
            $sheet->mergeCells('Q1:S1');
            $sheet->mergeCells('T1:V1');
            $sheet->mergeCells('W1:AB1');
            $sheet->mergeCells('AC1:AP1');
            $sheet->mergeCells('AQ1:AV1');
            $sheet->mergeCells('AW1:AZ1');

            $sheet->getStyle('A1:AZ2')->applyFromArray($styleArray); //apply style from array style array
            $sheet->getStyle('A1:AZ2')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK); // set cell border

            $sheet->getRowDimension(1)->setRowHeight(17); // row dimension
            $sheet->getRowDimension(2)->setRowHeight(30);

            $sheet->getDefaultColumnDimension()->setWidth(25); // set default column dimension
            $sheet->getStyle('A1:F2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFF8DC'); //column fill
            $sheet->getStyle('G1:N2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6FA');
            $sheet->getStyle('O1:P2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('A9A9A9');

            $sheet->getStyle('Q1:S2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('7FFFD4');
            $sheet->getStyle('T1:V2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F5DEB3');
            $sheet->getStyle('W1:AB2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F0E68C');
            $sheet->getStyle('AC1:AP2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFF5EE');
            $sheet->getStyle('AQ1:AV2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('F0FFFF');
            $sheet->getStyle('AW1:AZ2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('DDA0DD');


            $sheet->setCellValue('A1', 'Tester Identification');
            $sheet->SetCellValue('G1', 'Tester Contact Information');
            $sheet->SetCellValue('Q1', 'Testing Site In charge');
            $sheet->SetCellValue('T1', 'Facility In Charge');
            $sheet->SetCellValue('W1', 'Practical Examination');
            $sheet->SetCellValue('AC1', 'Facility In Charge');
            $sheet->SetCellValue('AQ1', 'Certification');
            $sheet->SetCellValue('AW1', 'Re-Certification');       

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
            $sheet->SetCellValue('N2', 'Facility Name');
            $sheet->SetCellValue('O2', 'Current job title');
            $sheet->SetCellValue('P2', 'Time worked as tester');
            $sheet->SetCellValue('Q2', 'Testing site in charge name');
            $sheet->SetCellValue('R2', 'Testing site in charge phone');
            $sheet->SetCellValue('S2', 'Testing site in charge email');
            $sheet->SetCellValue('T2', 'Facility in charge name');
            $sheet->SetCellValue('U2', 'Facility in charge phone');
            $sheet->SetCellValue('V2', 'Facility in charge email');
            $sheet->SetCellValue('W2', 'Practical exam date');
            $sheet->SetCellValue('X2', 'Practical exam admin');
            $sheet->SetCellValue('Y2', 'Practical exam number of attempt');
            $sheet->SetCellValue('Z2', 'Sample testing score');
            $sheet->SetCellValue('AA2', 'Direct observation score');
            $sheet->SetCellValue('AB2', 'Practical exam score');
            $sheet->SetCellValue('AC2', 'Written exam date');
            $sheet->SetCellValue('AD2', 'Written exam admin');
            $sheet->SetCellValue('AE2', 'Written exam number of attempt');
            $sheet->SetCellValue('AF2', 'QA (points)');
            $sheet->SetCellValue('AG2', 'RT (points)');
            $sheet->SetCellValue('AH2', 'Safety (points)');
            $sheet->SetCellValue('AI2', 'Specimen collection (points)');
            $sheet->SetCellValue('AJ2', 'Testing algorithm (points)');
            $sheet->SetCellValue('AK2', 'Record keeping (points)');
            $sheet->SetCellValue('AL2', 'EQA/PT (points)');
            $sheet->SetCellValue('AM2', 'Ethics (points)');
            $sheet->SetCellValue('AN2', 'Inevntory (points)');
            $sheet->SetCellValue('AO2', 'total point');
            $sheet->SetCellValue('AP2', 'Written exam score');
            $sheet->SetCellValue('AQ2', 'Final score');
            $sheet->SetCellValue('AR2', 'Final decision');
            $sheet->SetCellValue('AS2', 'Type of certification');
            $sheet->SetCellValue('AT2', 'Date certificate issued');
            $sheet->SetCellValue('AU2', 'certificate issuer');
            $sheet->SetCellValue('AV2', 'Due date');
            $sheet->SetCellValue('AW2', 'Type of reminder');
            $sheet->SetCellValue('AX2', 'Reminder sent to');
            $sheet->SetCellValue('AY2', 'Name of recipient');
            $sheet->SetCellValue('AZ2', 'Date reminder sent');

            $ligne = 3;
            $output = array();
            foreach($sResult as $aRow) {
                $row = array();
                $row[] = "111";
                $row[] = $aRow['certification_id'];
                $row[] = $aRow['professional_reg_no'];
                $row[] = (isset($excludeTesterName) && $excludeTesterName == 'yes') ? $aRow['last_name'] : '';
                $row[] = (isset($excludeTesterName) && $excludeTesterName == 'yes') ? $aRow['first_name'] : '';
                $row[] = (isset($excludeTesterName) && $excludeTesterName == 'yes') ? $aRow['middle_name'] : '';
                $row[] = (isset($aRow['country_name'])) ? $aRow['country_name'] : '';
                $row[] = (isset($aRow['region_name'])) ? $aRow['region_name'] : '';
                $row[] = (isset($aRow['district_name'])) ? $aRow['district_name'] : '';
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
                $row[] = date("d-m-Y", strtotime($aRow['practical_exam_date']));
                $row[] = $aRow['practical_exam_admin'];
                $row[] = $aRow['practical_exam_type'];
                $row[] = $aRow['Sample_testing_score'] . ' %';
                $row[] = $aRow['direct_observation_score'] . ' %';
                $row[] = $aRow['practical_total_score'] . ' %';
                $row[] = date("d-m-Y", strtotime($aRow['written_exam_date']));
                $row[] = $aRow['written_exam_admin'];
                $row[] = $aRow['written_exam_type'];
                $row[] = $aRow['qa_point'];
                $row[] = $aRow['rt_point'];
                $row[] = $aRow['safety_point'];
                $row[] = $aRow['specimen_point'];
                $row[] = $aRow['testing_algo_point'];
                $row[] = $aRow['report_keeping_point'];
                $row[] = $aRow['EQA_PT_points'];
                $row[] = $aRow['ethics_point'];
                $row[] = $aRow['inventory_point'];
                $row[] = $aRow['total_points'];
                $row[] = ($aRow['practical_total_score'] + $aRow['final_score']) / 2 . '  %';
                $row[] = $aRow['final_score'] . '  %';
                $row[] = $aRow['final_decision'];
                $row[] = $aRow['certification_type'];
                $row[] = date("d-m-Y", strtotime($aRow['date_certificate_issued']));
                $row[] = $aRow['certification_issuer'];
                $row[] = date("d-m-Y", strtotime($aRow['date_end_validity']));
                $row[] = $aRow['reminder_type'];
                $row[] = $aRow['reminder_sent_to'];
                $row[] = $aRow['name_of_recipient'];
                $row[] = "ttt";
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

            $sheet->getStyle('A2:AV2')->getAlignment()->setWrapText(true); // make a new line in cell
            $sheet->getStyle($sheet->calculateWorksheetDimension())->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);  //center column contain
            $writer = IOFactory::createWriter($excel, IOFactory::READER_XLSX);
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . date('d-m-Y') . '_Recertification_report.xlsx"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            exit;
        }

        return array(
            'countries' => $countries,
            'form' => $this->recertificationForm
        );
    }

    public function getRecertificateReportAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        $decision = $request->getPost('decision');
        $typeHiv = $request->getPost('typeHIV');
        $jobTitle = $request->getPost('jobTitle');
        $country = $request->getPost('country');
        $region = $request->getPost('region');
        $district = $request->getPost('district');
        $facility = $request->getPost('facility');
        $due_date = $request->getPost('due_date');
        $excludeTesterName = $request->getPost('exclude_tester_name');
        if (!empty($due_date)) {
            $array = explode(" ", $due_date);
            $startDate = date("Y-m-d", strtotime($array[0]));
            $endDate = date("Y-m-d", strtotime($array[2]));
        } else {
            //                
            $startDate = "";
            $endDate = "";
        }
        $reminder_type = $request->getPost('reminder_type');
        $reminder_sent_to = $request->getPost('reminder_sent_to');
        $date_reminder_sent = $request->getPost('date_reminder_sent');
        if (!empty($date_reminder_sent)) {
            $array2 = explode(" ", $date_reminder_sent);
            $startDate2 = date("Y-m-d", strtotime($array2[0]));
            $endDate2 = date("Y-m-d", strtotime($array2[2]));
        } else {
            //                
            $startDate2 = "";
            $endDate2 = "";
        }
        $result = $this->recertificationTable->report($startDate, $endDate, $decision, $typeHiv, $jobTitle, $country, $region, $district, $facility, $reminder_type, $reminder_sent_to, $startDate2, $endDate2);
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result));
        $viewModel->setTerminal(true);
        return $viewModel;
    }


    public function getRecertificateReportsAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->recertificationTable->reportData($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
}
