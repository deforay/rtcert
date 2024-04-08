<?php

namespace Certification\Controller;

use Laminas\Session\Container;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Json\Json;
use Laminas\View\Model\ViewModel;
use Certification\Model\Certification;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;

class CertificationController extends AbstractActionController
{

    public \Certification\Model\CertificationTable $certificationTable;
    public \Application\Service\CommonService $commonService;
    public \Certification\Form\CertificationForm $certificationForm;

    public function __construct($commonService, $certificationTable, $certificationForm)
    {
        $this->commonService = $commonService;
        $this->certificationForm = $certificationForm;
        $this->certificationTable = $certificationTable;
    }


    public function indexAction()
    {
        //$nb = $this->certificationTable->countCertificate();
        //$nb2 = $this->certificationTable->countReminder();
        //$this->layout()->setVariable('nb', $nb);
        //$this->layout()->setVariable('nb2', $nb2);
        $certification_id = (int) base64_decode($this->params()->fromQuery(base64_encode('certification_id'), null));
        $key = '';
        $src = '';
        if ($this->params()->fromQuery(base64_encode('key'), null)) {
            $key = base64_decode($this->params()->fromQuery(base64_encode('key'), null));
        }
        if ($this->params()->fromQuery('src', null)) {
            $src = $this->params()->fromQuery('src', null);
        }
        if ($certification_id !== 0 && !empty($key)) {
            $this->certificationTable->CertificateSent($certification_id);
            $container = new Container('alert');
            $container->alertMsg = 'Perform successfully';
            return $this->redirect()->toRoute('certification', array(
                'action' => 'edit',
                'certification_id' => base64_encode($certification_id)
            ));
        } else {
            return new ViewModel(array('src' => $src));
        }
    }

    public function addAction()
    {
        $this->indexAction();

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
        $certification_id = $this->certificationTable->certificationType($provider);

        $this->certificationForm->get('submit')->setValue('Submit');
        $this->certificationForm->get('provider')->setValue($provider);
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $certification = new Certification();
            $this->certificationForm->setInputFilter($certification->getInputFilter());
            $this->certificationForm->setData($request->getPost());

            if ($this->certificationForm->isValid()) {
                $certification->exchangeArray($this->certificationForm->getData());
                $this->certificationTable->saveCertification($certification);
                $last_id = $this->certificationTable->last_id();
                $this->certificationTable->updateExamination($last_id);
                $this->certificationTable->setToActive($last_id);
                if (empty($certification_id) && $written >= 80 && $direct >= 90 && $sample = 100) {
                    $this->certificationTable->certificationId($provider);
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
            'form' => $this->certificationForm
        );
    }

    public function editAction()
    {
        $this->indexAction();
        $id = (int) base64_decode($this->params()->fromRoute('id', 0));
        if ($id === 0) {
            return $this->redirect()->toRoute('certification', array(
                'action' => 'index'
            ));
        }

        try {
            $certification = $this->certificationTable->getCertification($id);
        } catch (\Exception $ex) {
            return $this->redirect()->toRoute('certification', array(
                'action' => 'index'
            ));
        }
        $certification->date_certificate_issued = date("d-m-Y", strtotime($certification->date_certificate_issued));
        if (isset($certification->date_certificate_sent) && $certification->date_certificate_sent != null && $certification->date_certificate_sent != '' && $certification->date_certificate_sent != '0000-00-00') {
            $certification->date_certificate_sent = date("d-m-Y", strtotime($certification->date_certificate_sent));
        } else {
            $certification->date_certificate_sent = '';
        }
        $provider = $this->certificationTable->getProvider($id);
        $this->certificationForm->bind($certification);
        $this->certificationForm->get('submit')->setAttribute('value', 'UPDATE');
        $this->certificationForm->get('provider')->setValue($provider);
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $this->certificationForm->setInputFilter($certification->getInputFilter());
            $this->certificationForm->setData($request->getPost());
            if ($this->certificationForm->isValid()) {
                $this->certificationTable->saveCertification($certification);
                $container = new Container('alert');
                $container->alertMsg = 'updated successfully';
                return $this->redirect()->toRoute('certification');
            }
        }

        return array(
            'id' => $id,
            'form' => $this->certificationForm
        );
    }

    public function pdfAction()
    {
        $showProfile =  $this->commonService->getGlobalValue('show-tester-photo-in-certificate');
        $header_text = $this->certificationTable->SelectTexteHeader();
        $header_text_font_size = $this->certificationTable->SelectHeaderTextFontSize();
        $globalConfigDetails = $this->certificationTable->fetchCertificationConfig();
        $id = base64_decode($this->params()->fromQuery(base64_encode('id')));
        $last = base64_decode($this->params()->fromQuery(base64_encode('last')));
        $first = base64_decode($this->params()->fromQuery(base64_encode('first')));
        $middle = base64_decode($this->params()->fromQuery(base64_encode('middle')));
        $certification_id = base64_decode($this->params()->fromQuery(base64_encode('certification_id')));
        $professional_reg_no = base64_decode($this->params()->fromQuery(base64_encode('professional_reg_no')));
        $date_issued = base64_decode($this->params()->fromQuery(base64_encode('date_issued')));
        $date_end_validity = $this->certificationTable->getCertificationValiditydate($id);

        $provider = $this->certificationTable->getProviderDetailsByCertifyId($id);
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

    public function xlsAction()
    {
        $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        $countries = $this->certificationTable->getAllActiveCountries();
        /** @var \Laminas\Http\Request $request */
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
            $sResult = $this->certificationTable->report($startDate, $endDate, $decision, $typeHiv, $jobTitle, $country, $region, $district, $facility);

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

            $sheet->getStyle('A1:AV2')->applyFromArray($styleArray); //apply style from array style array
            $sheet->getStyle('A1:AV2')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK); // set cell border

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

            $sheet->setCellValue('A1', 'Tester Identification');
            $sheet->SetCellValue('G1', 'Tester Contact Information');
            $sheet->SetCellValue('Q1', 'Testing Site In charge');
            $sheet->SetCellValue('T1', 'Facility In Charge');
            $sheet->SetCellValue('W1', 'Practical Examination');
            $sheet->SetCellValue('AC1', 'Facility In Charge');
            $sheet->SetCellValue('AQ1', 'Certificate');          

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
                $row[] = isset($aRow['practical_exam_date']) ? date("d-m-Y", strtotime($aRow['practical_exam_date'])) : '';
                $row[] = $aRow['practical_exam_admin'];
                $row[] = $aRow['practical_exam_type'];
                $row[] = isset($aRow['Sample_testing_score']) ? $aRow['Sample_testing_score'] . ' %' : '';
                $row[] = isset($aRow['direct_observation_score']) ? $aRow['direct_observation_score'] . ' %' : '';
                $row[] = isset($aRow['practical_total_score']) ? $aRow['practical_total_score'] . ' %' : '';
                $row[] = isset($aRow['written_exam_date']) ? date("d-m-Y", strtotime($aRow['written_exam_date'])) : '';
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
                $row[] = isset($aRow['date_certificate_issued']) ? date("d-m-Y", strtotime($aRow['date_certificate_issued'])) : '';
                $row[] = $aRow['certification_issuer'];
                $row[] = isset($aRow['date_end_validity']) ? date("d-m-Y", strtotime($aRow['date_end_validity'])) : '';
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
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            $writer = IOFactory::createWriter($excel, IOFactory::READER_XLSX);
            header('Content-Disposition: attachment;filename="' . date('d-m-Y') . '_Certification_report.xlsx"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            exit;
        }

        return array(
            'countries' => $countries,
        );
    }
    public function getCertificateReportAction()
    {
        /** @var \Laminas\Http\Request $request */
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
        $result = $this->certificationTable->report($startDate, $endDate, $decision, $typeHiv, $jobTitle, $country, $region, $district, $facility);
        // $result = $this->certificationTable->report($request);
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result));
        $viewModel->setTerminal(true);
        return $viewModel;
    }

    function pdfSettingAction()
    {


        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();

        //$nb = $this->certificationTable->countCertificate();
        //$nb2 = $this->certificationTable->countReminder();
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

            if (!file_exists(UPLOAD_PATH . DIRECTORY_SEPARATOR . "logo") && !is_dir(UPLOAD_PATH . DIRECTORY_SEPARATOR . "logo")) {
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
                } elseif (move_uploaded_file($imagetemp_left, $imagePath . DIRECTORY_SEPARATOR . 'logo_cert1.png')) {
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
                } elseif (move_uploaded_file($imagetemp_right, $imagePath . DIRECTORY_SEPARATOR . 'logo_cert2.png')) {
                    $msg_logo_right = 'image LOGO RIGHT loaded successfully';
                    //                    
                } else {
                    $msg_logo_right = "Failure to save the image : LOGO RIGHT. Try Again";
                }
            }

            $header_text = $request->getPost('header_text', null);
            $header_text_size = $request->getPost('header_text_font_size', null);
            //echo $header_text_size;die;

            if (trim($header_text) != '' || trim($header_text_size) != '') {
                $header_text = addslashes(trim($header_text));
                $stringWithoutBR = str_replace("\r\n", "<br />", $header_text);
                $this->certificationTable->insertTextHeader($stringWithoutBR, $header_text_size);
                $msg_header_text = "PDF Settings Saved Successfully.";
            }

            $headerText = $this->headerTextAction();
            $header_text_font_size = $this->certificationTable->SelectHeaderTextFontSize();
            return array(
                'msg_logo_left' => $msg_logo_left,
                'msg_logo_right' => $msg_logo_right,
                'msg_header_text' => $msg_header_text,
                'header_text' => $headerText['header_text'],
                'header_text_font_size' => $header_text_font_size
            );
        } else {

            $headerText = $this->headerTextAction();
            //die($headerText);
            $header_text_font_size = $this->certificationTable->SelectHeaderTextFontSize();
            //echo $header_text_font_size;die;
            return array(
                'header_text' => $headerText['header_text'],
                'header_text_font_size' => $header_text_font_size
            );
        }
    }

    public function headerTextAction()
    {
        $header_text = $this->certificationTable->SelectTexteHeader();
        return array('header_text' => $header_text);
    }

    public function recommendAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = 0;
            if (isset($params['recommendationRow']) && count($params['recommendationRow']) > 0) {
                $counter = count($params['recommendationRow']);
                for ($i = 0; $i < $counter; $i++) {
                    $examArray = explode('#', $params['recommendationRow'][$i]);
                    $certification_id = $this->certificationTable->certificationType($examArray[5]);
                    $certification = new Certification();
                    $certification->id = null;
                    $certification->provider = $examArray[5];
                    $certification->examination = $examArray[0];
                    $certification->final_decision = $examArray[6];
                    $certification->certification_issuer = $params['certificationIssuer'];
                    $certification->date_certificate_issued = null;
                    $certification->date_certificate_sent = null;
                    $certification->certification_type = empty($certification_id) ? 'Initial' : 'Recertification';
                    $result = $this->certificationTable->saveCertification($certification);
                    $last_id = $this->certificationTable->last_id();
                    $this->certificationTable->updateExamination($last_id);
                    $this->certificationTable->setToActive($last_id);
                    if (empty($certification_id) && $examArray[1] >= 80 && $examArray[3] >= 90 && $examArray[4] = 100) {
                        $this->certificationTable->certificationId($examArray[5]);
                    }
                }
            }
            $viewModel = new ViewModel(array(
                'result' => $result
            ));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }

    public function recommendedAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->certificationTable->fetchAllRecommended($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function approvalAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->certificationTable->updateCertficateApproval($params);
            $viewModel = new ViewModel(array(
                'result' => $result
            ));
            $viewModel->setTerminal(true);
            return $viewModel;
        }
    }

    public function toBeSentAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->certificationTable->fetchAllToBeSentCertificate($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function certifiedAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->certificationTable->fetchAllCertifiedTester($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function pendingAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->certificationTable->fetchAllFailedTester($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }


    public function certificationExpiryAction()
    {

        // $this->forward()->dispatch('Certification\Controller\CertificationController', array('action' => 'index'));
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $expirydata = $request->getPost('certificationExpiryVal');
            $country = $request->getPost('country_id');
            $region = $request->getPost('region');
            $district = $request->getPost('district');
            $sResult = $this->certificationTable->expiryReport($expirydata, $country, $region, $district);

            $excel = new Spreadsheet();
            $sheet = $excel->getActiveSheet();
            $styleArray = array(
                'font' => array(
                    'bold' => true,
                    'size' => 11,
                    'name' => 'Verdana',
                )
            );
            $sheet->mergeCells('A1:G1'); //merge some column
            $sheet->setCellValue('A1', 'Certification Expiry');
            $sheet->getStyle('A1:G2')->applyFromArray($styleArray); //apply style from array style array
            $sheet->getStyle('A1:G2')->getBorders()->getAllBorders()->setBorderStyle(\PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK); // set cell border

            $sheet->getRowDimension(1)->setRowHeight(17); // row dimension
            $sheet->getRowDimension(2)->setRowHeight(30);

            $sheet->getDefaultColumnDimension()->setWidth(25);

            $sheet->getStyle('A1:G2')->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB('FFF8DC'); //column fill


            $sheet->SetCellValue('A2', 'Tester');
            $sheet->SetCellValue('B2', 'Final Decision');
            $sheet->SetCellValue('C2', 'Region');
            $sheet->SetCellValue('D2', 'District');
            $sheet->SetCellValue('E2', 'Facility');
            $sheet->SetCellValue('F2', 'Type HIV testing modality/point');
            $sheet->SetCellValue('G2', 'Current job title');

            $output = array();
            foreach($sResult as $aRow) {
                $row = array();
                $row[] = $aRow['first_name'] . ' ' . $aRow['last_name'];
                $row[] = $aRow['final_decision'];
                $row[] = $aRow['region_name'];
                $row[] = $aRow['district_name'];
                $row[] = $aRow['facility_name'];
                $row[] = $aRow['type_vih_test'];
                $row[] = $aRow['current_jod'];
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
            header('Content-Disposition: attachment;filename="' . date('d-m-Y') . '_list of all ' . $expirydata . '.xlsx"');
            header('Cache-Control: max-age=0');
            $writer->save('php://output');
            exit;
        }
        return array('country' => $this->commonService->getAllActiveCountries());
    }


    public function getExpiryCertificateReportAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        $expirydata = $request->getPost('expirycertification');
        $country = $request->getPost('country_id');
        $region = $request->getPost('region');
        $district = $request->getPost('district');
        $result = $this->certificationTable->expiryReport($expirydata, $country, $region, $district);
        $viewModel = new ViewModel();
        $viewModel->setVariables(array('result' => $result, 'params' => $expirydata));
        $viewModel->setTerminal(true);
        return $viewModel;
    }


    public function getCertificateReportsAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->certificationTable->reportData($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function getExpiryCertificateReportsAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->certificationTable->expiryReportData($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }
    public function sendMultipleCertificationMailAction()
    {
        $request = $this->getRequest();
        $container = new Container('alert');
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $tempId = $this->commonService->sendMultipleCertificationMail($parameters);
            if($tempId  > 0){
                $container->alertMsg = 'Mail queued successfully';
                return $this->redirect()->toRoute('certification');
            }else{
                $container->alertMsg = 'Mail not queued';
                return $this->redirect()->toRoute('certification');
            }  
        }
    }
}
