<?php
namespace Certification\Controller;

use Zend\Session\Container;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\Json\Json;
use Zend\View\Model\ViewModel;
use Certification\Model\Certification;
use Certification\Form\CertificationForm;
use Zend\Debug\Debug;

class CertificationController extends AbstractActionController {

    protected $certificationTable;

    public function getCertificationTable() {
        if (!$this->certificationTable) {
            $sm = $this->getServiceLocator();
            $this->certificationTable = $sm->get('Certification\Model\CertificationTable');
        }
        return $this->certificationTable;
    }

    public function indexAction() {
        //$nb = $this->getCertificationTable()->countCertificate();
        //$nb2 = $this->getCertificationTable()->countReminder();
        //$this->layout()->setVariable('nb', $nb);
        //$this->layout()->setVariable('nb2', $nb2);
        $certification_id = (int) base64_decode($this->params()->fromQuery(base64_encode('certification_id'), null));
        $key = '';
        $src = '';
        if($this->params()->fromQuery(base64_encode('key'), null)){
           $key = base64_decode($this->params()->fromQuery(base64_encode('key'), null));
        }
        if($this->params()->fromQuery('src', null)){
            $src = $this->params()->fromQuery('src', null);
        }
        if (!empty($certification_id) && !empty($key)) {
            $this->getCertificationTable()->CertificateSent($certification_id);
            $container = new Container('alert');
            $container->alertMsg = 'Perform successfully';
            return $this->redirect()->toRoute('certification', array(
                        'action' => 'edit',
                        'certification_id' => base64_encode($certification_id)));
        } else {
            return new ViewModel(array('src'=>$src));
        }
    }

    public function addAction() {
        $this->indexAction();
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');

        $id = (int) base64_decode($this->params()->fromRoute('id', 0));
//        die($id);
        $written = (int) base64_decode($this->params()->fromQuery(base64_encode('written')));
        $practical = (int) base64_decode($this->params()->fromQuery(base64_encode('practical')));
        $sample = (int) base64_decode($this->params()->fromQuery(base64_encode('sample')));
        $direct = (int) base64_decode($this->params()->fromQuery(base64_encode('direct')));
        if (!$id || !$written || !$practical || !$sample || !$direct) {

            return $this->redirect()->toRoute('examination');
        }
        $provider = (int) base64_decode($this->params()->fromQuery(base64_encode('provider')));
        $certification_id = $this->getCertificationTable()->certificationType($provider);

        $form = new CertificationForm($dbAdapter);
        $form->get('submit')->setValue('Submit');
        $form->get('provider')->setValue($provider);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $certification = new Certification();
            $form->setInputFilter($certification->getInputFilter());
            $form->setData($request->getPost());

            if ($form->isValid()) {
                $certification->exchangeArray($form->getData());
                $this->getCertificationTable()->saveCertification($certification);
                $last_id = $this->getCertificationTable()->last_id();
                $this->getCertificationTable()->updateExamination($last_id);
                $this->getCertificationTable()->setToActive($last_id);
                if (empty($certification_id) && $written >= 80 && $direct >= 90 && $sample = 100) {
                    $this->getCertificationTable()->certificationId($provider);
                }
                $container = new Container('alert');
                $container->alertMsg = 'Added successfully';
                return $this->redirect()->toRoute('examination');
            }
        }
        return array(
            'id' => $id,
            'written' => $written,
            'practical' => $practical,
            'sample' => $sample,
            'direct' => $direct,
            'certification_id' => $certification_id,
            'form' => $form
            );
    }

    public function editAction() {
        $this->indexAction();
        $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
        $id = (int) base64_decode($this->params()->fromRoute('id', 0));
        if (!$id) {
            return $this->redirect()->toRoute('certification', array(
                        'action' => 'index'));
        }

        try {
            $certification = $this->getCertificationTable()->getCertification($id);
            
        } catch (\Exception $ex) {
            return $this->redirect()->toRoute('certification', array(
                        'action' => 'index'
            ));
        }
        $certification->date_certificate_issued = date("d-m-Y", strtotime($certification->date_certificate_issued));
        if (isset($certification->date_certificate_sent) && $certification->date_certificate_sent!= null && $certification->date_certificate_sent!= '' && $certification->date_certificate_sent!= '0000-00-00') {
            $certification->date_certificate_sent = date("d-m-Y", strtotime($certification->date_certificate_sent));
        }else{
            $certification->date_certificate_sent = '';
        }
        $provider = $this->getCertificationTable()->getProvider($id);
        $form = new CertificationForm($dbAdapter);
        $form->bind($certification);
        $form->get('submit')->setAttribute('value', 'UPDATE');
        $form->get('provider')->setValue($provider);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $form->setInputFilter($certification->getInputFilter());
            $form->setData($request->getPost());
            if ($form->isValid()) {
                $this->getCertificationTable()->saveCertification($certification);
                $container = new Container('alert');
                $container->alertMsg = 'updated successfully';
                return $this->redirect()->toRoute('certification');
            }
        }

        return array(
            'id' => $id,
            'form' => $form,
        );
    }

    public function pdfAction() {
        $common = $this->getServiceLocator()->get('CommonService');
        $showProfile =  $common->getGlobalValue('show-tester-photo-in-certificate');
        $header_text = $this->getCertificationTable()->SelectTexteHeader();
        $header_text_font_size = $this->getCertificationTable()->SelectHeaderTextFontSize();
        $globalConfigDetails = $this->getCertificationTable()->fetchCertificationConfig();
        $id = base64_decode($this->params()->fromQuery(base64_encode('id')));
        $last = base64_decode($this->params()->fromQuery(base64_encode('last')));
        $first = base64_decode($this->params()->fromQuery(base64_encode('first')));
        $middle = base64_decode($this->params()->fromQuery(base64_encode('middle')));
        $certification_id = base64_decode($this->params()->fromQuery(base64_encode('certification_id')));
        $professional_reg_no = base64_decode($this->params()->fromQuery(base64_encode('professional_reg_no')));
        $date_issued = base64_decode($this->params()->fromQuery(base64_encode('date_issued')));
        $date_end_validity = $this->getCertificationTable()->getCertificationValiditydate($id);

        $provider = $this->getCertificationTable()->getProviderDetailsByCertifyId($id);
        // Debug::dump($provider['profile_picture']);die;
        return array(
            'last'                  => $last,
            'first'                 => $first,
            'middle'                => $middle,
            'professional_reg_no'   => $professional_reg_no,
            'certification_id'      => $certification_id,
            'date_issued'           => $date_issued,
            'date_end_validity'     => $date_end_validity,
            'header_text'           => $header_text,
            'header_text_font_size' => $header_text_font_size,
            'config_info'           => $globalConfigDetails,
            'provider'              => $provider,
            'showProfile'           => $showProfile
        );
    }

    public function xlsAction() {
        $this->forward()->dispatch('Certification\Controller\Certification', array('action' => 'index'));
        $countries = $this->getCertificationTable()->getAllActiveCountries();
        $request = $this->getRequest();
        if ($request->isPost()) {
            $decision = $request->getPost('decision');
            $typeHiv = $request->getPost('typeHIV');
            $jobTitle = $request->getPost('jobTitle');
            $dateRange = $request->getPost('dateRange');
            if (!empty($dateRange)) {
                $array = explode(" ", $dateRange);
                $startDate = date("Y-m-d", strtotime($array[0]));
                $endDate = date("Y-m-d", strtotime($array[2]));
            } else {
                $startDate = "";
                $endDate = "";
            }
            $country = $request->getPost('country');
            $region = $request->getPost('region');
            $district = $request->getPost('district');
            $excludeTesterName = $request->getPost('exclude_tester_name');
            $facility = $request->getPost('facility');
            $result = $this->getCertificationTable()->report($startDate, $endDate, $decision, $typeHiv, $jobTitle, $country, $region, $district, $facility);

            // $dbAdapter = $this->getServiceLocator()->get('Zend\Db\Adapter\Adapter');
            // $sql = new Sql($dbAdapter);
            // $queryContainer = new Container('query');
           
            // $queryStr = $sql->getSqlStringForSqlObject($queryContainer->exportAllEvents);
            // $sResult = $dbAdapter->query($queryStr, $dbAdapter::QUERY_MODE_EXECUTE)->toArray();

            $objPHPExcel = new \PHPExcel();
            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size' => 11,
                    'name' => 'Verdana',
            ));


            $objPHPExcel->setActiveSheetIndex(0);

            $objPHPExcel->setActiveSheetIndex()->mergeCells('A1:F1'); //merge some column
            $objPHPExcel->setActiveSheetIndex()->mergeCells('G1:N1');
            $objPHPExcel->setActiveSheetIndex()->mergeCells('Q1:S1');
            $objPHPExcel->setActiveSheetIndex()->mergeCells('T1:V1');
            $objPHPExcel->setActiveSheetIndex()->mergeCells('W1:AB1');
            $objPHPExcel->setActiveSheetIndex()->mergeCells('AC1:AP1');
            $objPHPExcel->setActiveSheetIndex()->mergeCells('AQ1:AV1');

            $objPHPExcel->getActiveSheet()->getStyle('A1:AV2')->applyFromArray($styleArray); //apply style from array style array
            $objPHPExcel->getActiveSheet()->getStyle('A1:AV2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THICK); // set cell border

            $objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(17); // row dimension
            $objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(30);

            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(25); // set default column dimension
            $objPHPExcel->getActiveSheet()->getStyle('A1:F2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FFF8DC'); //column fill
            $objPHPExcel->getActiveSheet()->getStyle('G1:N2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6FA');
            $objPHPExcel->getActiveSheet()->getStyle('O1:P2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('A9A9A9');

            $objPHPExcel->getActiveSheet()->getStyle('Q1:S2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('7FFFD4');
            $objPHPExcel->getActiveSheet()->getStyle('T1:V2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('F5DEB3');
            $objPHPExcel->getActiveSheet()->getStyle('W1:AB2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('F0E68C');
            $objPHPExcel->getActiveSheet()->getStyle('AC1:AP2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FFF5EE');
            $objPHPExcel->getActiveSheet()->getStyle('AQ1:AV2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('F0FFFF');


            $objPHPExcel->getActiveSheet()->setCellValue('A1', 'Tester Identification');
            $objPHPExcel->getActiveSheet()->SetCellValue('G1', 'Tester Contact Information');
            $objPHPExcel->getActiveSheet()->SetCellValue('Q1', 'Testing Site In charge');
            $objPHPExcel->getActiveSheet()->SetCellValue('T1', 'Facility In Charge');
            $objPHPExcel->getActiveSheet()->SetCellValue('W1', 'Practical Examination');
            $objPHPExcel->getActiveSheet()->SetCellValue('AC1', 'Facility In Charge');
            $objPHPExcel->getActiveSheet()->SetCellValue('AQ1', 'Certificate');
//           

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
            $objPHPExcel->getActiveSheet()->SetCellValue('N2', 'Facility Name');
            $objPHPExcel->getActiveSheet()->SetCellValue('O2', 'Current job title');
            $objPHPExcel->getActiveSheet()->SetCellValue('P2', 'Time worked as tester');
            $objPHPExcel->getActiveSheet()->SetCellValue('Q2', 'Testing site in charge name');
            $objPHPExcel->getActiveSheet()->SetCellValue('R2', 'Testing site in charge phone');
            $objPHPExcel->getActiveSheet()->SetCellValue('S2', 'Testing site in charge email');
            $objPHPExcel->getActiveSheet()->SetCellValue('T2', 'Facility in charge name');
            $objPHPExcel->getActiveSheet()->SetCellValue('U2', 'Facility in charge phone');
            $objPHPExcel->getActiveSheet()->SetCellValue('V2', 'Facility in charge email');
            $objPHPExcel->getActiveSheet()->SetCellValue('W2', 'Practical exam date');
            $objPHPExcel->getActiveSheet()->SetCellValue('X2', 'Practical exam admin');
            $objPHPExcel->getActiveSheet()->SetCellValue('Y2', 'Practical exam number of attempt');
            $objPHPExcel->getActiveSheet()->SetCellValue('Z2', 'Sample testing score');
            $objPHPExcel->getActiveSheet()->SetCellValue('AA2', 'Direct observation score');
            $objPHPExcel->getActiveSheet()->SetCellValue('AB2', 'Practical exam score');
            $objPHPExcel->getActiveSheet()->SetCellValue('AC2', 'Written exam date');
            $objPHPExcel->getActiveSheet()->SetCellValue('AD2', 'Written exam admin');
            $objPHPExcel->getActiveSheet()->SetCellValue('AE2', 'Written exam number of attempt');
            $objPHPExcel->getActiveSheet()->SetCellValue('AF2', 'QA (points)');
            $objPHPExcel->getActiveSheet()->SetCellValue('AG2', 'RT (points)');
            $objPHPExcel->getActiveSheet()->SetCellValue('AH2', 'Safety (points)');
            $objPHPExcel->getActiveSheet()->SetCellValue('AI2', 'Specimen collection (points)');
            $objPHPExcel->getActiveSheet()->SetCellValue('AJ2', 'Testing algorithm (points)');
            $objPHPExcel->getActiveSheet()->SetCellValue('AK2', 'Record keeping (points)');
            $objPHPExcel->getActiveSheet()->SetCellValue('AL2', 'EQA/PT (points)');
            $objPHPExcel->getActiveSheet()->SetCellValue('AM2', 'Ethics (points)');
            $objPHPExcel->getActiveSheet()->SetCellValue('AN2', 'Inevntory (points)');
            $objPHPExcel->getActiveSheet()->SetCellValue('AO2', 'total point');
            $objPHPExcel->getActiveSheet()->SetCellValue('AP2', 'Written exam score');
            $objPHPExcel->getActiveSheet()->SetCellValue('AQ2', 'Final score');
            $objPHPExcel->getActiveSheet()->SetCellValue('AR2', 'Final decision');
            $objPHPExcel->getActiveSheet()->SetCellValue('AS2', 'Type of certification');
            $objPHPExcel->getActiveSheet()->SetCellValue('AT2', 'Date certificate issued');
            $objPHPExcel->getActiveSheet()->SetCellValue('AU2', 'certificate issuer');
            $objPHPExcel->getActiveSheet()->SetCellValue('AV2', 'Due date');

            $ligne = 3;
            foreach ($result as $result) {
////           
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, $ligne, $result['certification_reg_no']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1, $ligne, $result['certification_id']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(2, $ligne, $result['professional_reg_no']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(3, $ligne, (isset($excludeTesterName) && $excludeTesterName == 'yes')?$result['last_name']:'');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(4, $ligne, (isset($excludeTesterName) && $excludeTesterName == 'yes')?$result['first_name']:'');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(5, $ligne, (isset($excludeTesterName) && $excludeTesterName == 'yes')?$result['middle_name']:'');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(6, $ligne, $result['country_name']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(7, $ligne, $result['region_name']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(8, $ligne, $result['district_name']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(9, $ligne, $result['type_vih_test']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(10, $ligne, $result['phone']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(11, $ligne, $result['email']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(12, $ligne, $result['prefered_contact_method']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(13, $ligne, $result['facility_name']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(14, $ligne, $result['current_jod']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(15, $ligne, $result['time_worked']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(16, $ligne, $result['test_site_in_charge_name']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(17, $ligne, $result['test_site_in_charge_phone']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(18, $ligne, $result['test_site_in_charge_email']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(19, $ligne, $result['facility_in_charge_name']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(20, $ligne, $result['facility_in_charge_phone']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(21, $ligne, $result['facility_in_charge_email']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(22, $ligne, date("d-m-Y", strtotime($result['practical_exam_date'])));
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(23, $ligne, $result['practical_exam_admin']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(24, $ligne, $result['practical_exam_type']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(25, $ligne, $result['direct_observation_score'] . ' %');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(26, $ligne, $result['Sample_testing_score'] . ' %');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(27, $ligne, $result['practical_total_score'] . ' %');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(28, $ligne, date("d-m-Y", strtotime($result['written_exam_date'])));
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(29, $ligne, $result['written_exam_admin']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(30, $ligne, $result['written_exam_type']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(31, $ligne, $result['qa_point']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(32, $ligne, $result['rt_point']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(33, $ligne, $result['safety_point']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(35, $ligne, $result['testing_algo_point']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(34, $ligne, $result['specimen_point']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(36, $ligne, $result['report_keeping_point']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(37, $ligne, $result['EQA_PT_points']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(38, $ligne, $result['ethics_point']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(39, $ligne, $result['inventory_point']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(40, $ligne, $result['total_points']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(41, $ligne, $result['final_score'] . '  %');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(42, $ligne, ($result['practical_total_score'] + $result['final_score']) / 2 . '  %');
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(43, $ligne, $result['final_decision']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(44, $ligne, $result['certification_type']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(45, $ligne, date("d-m-Y", strtotime($result['date_certificate_issued'])));
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(46, $ligne, $result['certification_issuer']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(47, $ligne, date("d-m-Y", strtotime($result['date_end_validity'])));

                $ligne++;
            }

            $objPHPExcel->getActiveSheet()->getStyle('A2:AV2')->getAlignment()->setWrapText(true); // make a new line in cell
            $objPHPExcel->getActiveSheet()->getStyle($objPHPExcel->getActiveSheet()->calculateWorksheetDimension())->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  //center column contain
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);
            header('Content-Disposition: attachment;filename="' . date('d-m-Y') . '_Certification_report.xlsx"');
            header('Cache-Control: max-age=0');
            $objWriter->save('php://output');
            exit;
        }

        return array(
            'countries' => $countries,
        );
        
    }
    public function getCertificateReportAction()
    {
        $request = $this->getRequest();
        $decision = $request->getPost('decision');
        $typeHiv = $request->getPost('typeHIV');
        $jobTitle = $request->getPost('jobTitle');
        $dateRange = $request->getPost('dateRange');
        if (!empty($dateRange)) {
            $array = explode(" ", $dateRange);
            $startDate = date("Y-m-d", strtotime($array[0]));
            $endDate = date("Y-m-d", strtotime($array[2]));
        } else {
            $startDate = "";
            $endDate = "";
        }
        $country = $request->getPost('country');
        $region = $request->getPost('region');
        $district = $request->getPost('district');
        $excludeTesterName = $request->getPost('exclude_tester_name');
        $facility = $request->getPost('facility');
        $result = $this->getCertificationTable()->report($startDate, $endDate, $decision, $typeHiv, $jobTitle, $country, $region, $district, $facility);
        // $result = $this->getCertificationTable()->report($request);
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' =>$result));
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    function pdfSettingAction() {

        
        $request = $this->getRequest();

        //$nb = $this->getCertificationTable()->countCertificate();
        //$nb2 = $this->getCertificationTable()->countReminder();
        //$this->layout()->setVariable('nb', $nb);
        //$this->layout()->setVariable('nb2', $nb2);

        if ($request->isPost()) {
            //\Zend\Debug\Debug::dump($_FILES);die;
            $image_left = $request->getPost('logo_left', null);
            //Stores the filename as it was on the client computer.
            $imagename_left = $_FILES['logo_left']['name'];
            //Stores the filetype e.g image/jpeg
            $imagetype_left = $_FILES['logo_left']['type'];
            //Stores any error codes from the upload.
            $imageerror_left = $_FILES['logo_left']['error'];
            //Stores the tempname as it is given by the host when uploaded.
            $imagetemp_left = $_FILES['logo_left']['tmp_name'];

            $image_right = $request->getPost('logo_right', null);
            $imagename_right = $_FILES['logo_right']['name'];
            $imagetype_right = $_FILES['logo_right']['type'];
            $imageerror_right = $_FILES['logo_right']['error'];
            $imagetemp_right = $_FILES['logo_right']['tmp_name'];

            $msg_logo_left = '';
            $msg_logo_right = '';
            $msg_header_text = '';

            //The path you wish to upload the image to
            //$imagePath = $_SERVER["DOCUMENT_ROOT"] . '/assets/img/';
            
            if(!file_exists(UPLOAD_PATH . DIRECTORY_SEPARATOR . "logo") && !is_dir(UPLOAD_PATH . DIRECTORY_SEPARATOR . "logo")) {
                mkdir(UPLOAD_PATH . DIRECTORY_SEPARATOR . "logo");
            }

            $imagePath = UPLOAD_PATH . DIRECTORY_SEPARATOR . "logo";
            // echo $imagePath;die;
            if (is_uploaded_file($imagetemp_left)) {
                $array_type = explode('/', $imagetype_left);
                list($width, $height, $type, $attr) = getimagesize($imagetemp_left);

                if (strcasecmp($array_type[1], 'png') != 0) {
                    $msg_logo_left = 'You must load an image in PNG format for LOGO LEFT.';
                } elseif ($width > 425 || $height > 352) {
                    $msg_logo_left = 'The size of your image LOGO LEFT should not exceed: 425x352.';
                } elseif (move_uploaded_file($imagetemp_left, $imagePath .DIRECTORY_SEPARATOR. 'logo_cert1.png')) {
                    $msg_logo_left = 'Image LOGO LEFT loaded successfully';                    
                } else {
                    $msg_logo_left = "Failure to save the image: LOGO LEFT. Try Again";
                }
            }

            if (is_uploaded_file($imagetemp_right)) {
                $array_type = explode('/', $imagetype_right);
                list($width, $height, $type, $attr) = getimagesize($imagetemp_right);

                if (strcasecmp($array_type[1], 'png') != 0) {
                    $msg_logo_right = 'You must load an image in PNG format for LOGO RIGHT.';
                } elseif ($width > 425 || $height > 352) {
                    $msg_logo_right = 'the size of your image LOGO RIGHT should not exceed: 425x352.';
                } elseif (move_uploaded_file($imagetemp_right, $imagePath.DIRECTORY_SEPARATOR . 'logo_cert2.png')) {
                    $msg_logo_right = 'image LOGO RIGHT loaded successfully';
//                    
                } else {
                    $msg_logo_right = "Failure to save the image : LOGO RIGHT. Try Again";
                }
            }

            $header_text = $request->getPost('header_text', null);
            $header_text_size = $request->getPost('header_text_font_size', null);
            //echo $header_text_size;die;

            if (trim($header_text) != '' || trim($header_text_size)!= '') {
                $header_text = addslashes(trim($header_text));
                $stringWithoutBR = str_replace("\r\n", "<br />", $header_text);
                $this->getCertificationTable()->insertTextHeader($stringWithoutBR,$header_text_size);
                $msg_header_text = "PDF Settings Saved Successfully.";
            }

            $headerText = $this->headerTextAction();
            $header_text_font_size = $this->getCertificationTable()->SelectHeaderTextFontSize();
            return array('msg_logo_left' => $msg_logo_left,
                'msg_logo_right' => $msg_logo_right,
                'msg_header_text' => $msg_header_text,
                'header_text' => $headerText['header_text'],
                'header_text_font_size' => $header_text_font_size
            );
        }else{
            
            $headerText = $this->headerTextAction();
            //die($headerText);
            $header_text_font_size = $this->getCertificationTable()->SelectHeaderTextFontSize();
            //echo $header_text_font_size;die;
            return array(
                'header_text' => $headerText['header_text'],
                'header_text_font_size' => $header_text_font_size
            );
        }
    }

    public function headerTextAction() {
        $header_text = $this->getCertificationTable()->SelectTexteHeader();
        return array('header_text' => $header_text);
    }
    
    public function recommendAction() {
       $request = $this->getRequest();                
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = 0;
            if(isset($params['recommendationRow']) && count($params['recommendationRow']) > 0){
                for($i=0;$i<count($params['recommendationRow']);$i++){
                    $examArray = explode('#',$params['recommendationRow'][$i]);
                    $certification_id = $this->getCertificationTable()->certificationType($examArray[5]);
                    $certification = new Certification();
                    $certification->id = null;
                    $certification->provider = $examArray[5];
                    $certification->examination = $examArray[0];
                    $certification->final_decision = $examArray[6];
                    $certification->certification_issuer = $params['certificationIssuer'];
                    $certification->date_certificate_issued = null;
                    $certification->date_certificate_sent = null;
                    if (empty($certification_id)) {
                      $certification->certification_type = 'Initial';
                    }else{
                       $certification->certification_type = 'Recertification'; 
                    }
                    $result = $this->getCertificationTable()->saveCertification($certification);
                    $last_id = $this->getCertificationTable()->last_id();
                    $this->getCertificationTable()->updateExamination($last_id);
                    $this->getCertificationTable()->setToActive($last_id);
                    if (empty($certification_id) && $examArray[1] >= 80 && $examArray[3] >= 90 && $examArray[4] = 100) {
                        $this->getCertificationTable()->certificationId($examArray[5]);
                    }
                }
            }
            $viewModel = new ViewModel(array(
                'result'=>$result
                ));
            $viewModel->setTerminal(true);
            return $viewModel;
        } 
    }
    
    public function recommendedAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->getCertificationTable()->fetchAllRecommended($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
    
    public function approvalAction(){
        $request = $this->getRequest();                
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->getCertificationTable()->updateCertficateApproval($params);
            $viewModel = new ViewModel(array(
                'result'=>$result
                ));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function toBeSentAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->getCertificationTable()->fetchAllToBeSentCertificate($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
    
    public function certifiedAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->getCertificationTable()->fetchAllCertifiedTester($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
    
    public function pendingAction() {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->getCertificationTable()->fetchAllFailedTester($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }


    public function certificationExpiryAction() {
        
        // $this->forward()->dispatch('Certification\Controller\Certification', array('action' => 'index'));
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $expirydata = $request->getPost('certificationExpiryVal');
            $country = $request->getPost('country_id');
            $region = $request->getPost('region');
            $district = $request->getPost('district');
            $examination =$this->getCertificationTable()->expiryReport($expirydata,$country, $region, $district);        
            
            $objPHPExcel = new \PHPExcel();
            $objPHPExcel->setActiveSheetIndex(0);
            $objPHPExcel->setActiveSheetIndex()->mergeCells('A1:F1'); //merge some column

            $objPHPExcel->getActiveSheet()->setCellValue('A1', 'Certification Expiry');

            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size' => 11,
                    'name' => 'Verdana',
            ));
            $objPHPExcel->getActiveSheet()->getStyle('A1:V2')->applyFromArray($styleArray); //apply style from array style array
            $objPHPExcel->getActiveSheet()->getStyle('A1:V2')->getBorders()->getAllBorders()->setBorderStyle(\PHPExcel_Style_Border::BORDER_THICK); // set cell border

            $objPHPExcel->getActiveSheet()->getRowDimension(1)->setRowHeight(17); // row dimension
            $objPHPExcel->getActiveSheet()->getRowDimension(2)->setRowHeight(30);

            $objPHPExcel->getActiveSheet()->getDefaultColumnDimension()->setWidth(25);

            $objPHPExcel->getActiveSheet()->getStyle('A1:F2')->getFill()->setFillType(\PHPExcel_Style_Fill::FILL_SOLID)->getStartColor()->setRGB('FFF8DC'); //column fill
          
  
            $objPHPExcel->getActiveSheet()->SetCellValue('A2', 'Tester');
            $objPHPExcel->getActiveSheet()->SetCellValue('B2', 'Final Decision');
            $objPHPExcel->getActiveSheet()->SetCellValue('C2', 'Region');
            $objPHPExcel->getActiveSheet()->SetCellValue('D2', 'District');
            $objPHPExcel->getActiveSheet()->SetCellValue('E2', 'Facility');
            $objPHPExcel->getActiveSheet()->SetCellValue('F2', 'Type HIV testing modality/point');
            $objPHPExcel->getActiveSheet()->SetCellValue('G2', 'Current job title');
            $ligne = 3;
            foreach ($examination as $examination) {
                
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(0, $ligne, $examination['first_name'].' '.$examination['last_name']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(1, $ligne, $examination['final_decision']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(2, $ligne, $examination['region_name']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(3, $ligne, $examination['district_name']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(4, $ligne, $examination['facility_name']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(5, $ligne, $examination['type_vih_test']);
                $objPHPExcel->getActiveSheet()->setCellValueByColumnAndRow(6, $ligne, $examination['current_jod']);
                $ligne++;
            }
            $objPHPExcel->getActiveSheet()->getStyle('A2:U2')->getAlignment()->setWrapText(true); // make a new line in cell
            $objPHPExcel->getActiveSheet()->getStyle($objPHPExcel->getActiveSheet()->calculateWorksheetDimension())->getAlignment()->setHorizontal(\PHPExcel_Style_Alignment::HORIZONTAL_CENTER);  //center column contain

            $objWriter = new \PHPExcel_Writer_Excel2007($objPHPExcel);

            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . date('d-m-Y') . '_list of all '.$expirydata.'.xlsx"');
            header('Cache-Control: max-age=0');
            $objWriter->save('php://output');
            exit;
        }
        $common = $this->getServiceLocator()->get('CommonService');
        return array('country' => $common->getAllActiveCountries());
    }


    public function getExpiryCertificateReportAction()
    {
        $request = $this->getRequest();
        $expirydata = $request->getPost('expirycertification');
        $country = $request->getPost('country_id');
        $region = $request->getPost('region');
        $district = $request->getPost('district');
        $result = $this->getCertificationTable()->expiryReport($expirydata,$country, $region, $district);        
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' =>$result, 'params' => $expirydata));
        $viewModel->setTerminal(true);
        return $viewModel;

    }

    
    public function getCertificateReportsAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->getCertificationTable()->reportData($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
    
    public function getExpiryCertificateReportsAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->getCertificationTable()->expiryReportData($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
    


}
