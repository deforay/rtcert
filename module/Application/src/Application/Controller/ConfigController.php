<?php

namespace Application\Controller;


use Laminas\Mvc\Controller\AbstractActionController;
use Intervention\Image\ImageManager;
use Laminas\View\Model\ViewModel;
use Laminas\Session\Container;
use Laminas\Json\Json;

class ConfigController extends AbstractActionController
{
    public \Application\Service\CommonService $commonService;
    public \Certification\Model\CertificationTable $certificationTable;

    public function __construct($commonService, $certificationTable)
    {
        $this->commonService = $commonService;
        $this->certificationTable = $certificationTable;
    }

    public function indexAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->commonService->getAllConfig($params);
            return $this->getResponse()->setContent(Json::encode($result));
        }
        $result = $this->commonService->getHeaderText();
        // $result['show_tester_photo_in_certificate'] = $this->commonService->getGlobalValue('show_tester_photo_in_certificate');
        return new ViewModel(array(
            'header_text'                       => $result['textHeader'],
            'header_text_font_size'             => $result['HeaderTextFont'],
            // 'show_tester_photo_in_certificate'  => $result['show_tester_photo_in_certificate']
        ));
    }

    public function editGlobalAction()
    {
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $this->commonService->updateConfig($params);
            return $this->redirect()->toRoute('config');
        } else {
            $configResult = $this->commonService->getGlobalConfigDetails();
            return new ViewModel(array(
                'config' => $configResult,
            ));
        }
    }

    function pdfSettingAction()
    {

        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();

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
            $maxSize = 300;

            if (is_uploaded_file($imagetemp_left)) {
                $array_type = explode('/', $imagetype_left);
    
                if (strcasecmp($array_type[1], 'png') != 0) {
                    $msg_logo_left = 'You must load an image in PNG format for LOGO LEFT.';
                } else {
                    $img = ImageManager::imagick()->read($imagetemp_left);
                    $img->scale(width: $maxSize);
    
                    // Save the resized image
                    $saved = $img->save($imagePath . DIRECTORY_SEPARATOR . 'logo_cert1.png');
    
                    if (!$saved) {
                        $msg_logo_left = "Failure to save the image: LOGO LEFT. Try Again";
                    }
                }
            }

            if (is_uploaded_file($imagetemp_right)) {
                $array_type = explode('/', $imagetype_right);
    
                if (strcasecmp($array_type[1], 'png') != 0) {
                    $msg_logo_right = 'You must load an image in PNG format for LOGO RIGHT.';
                } else {
                    $img = ImageManager::imagick()->read($imagetemp_right);
                    $img->scale(width: $maxSize);
    
                    // Save the resized image
                    $saved = $img->save($imagePath . DIRECTORY_SEPARATOR . 'logo_cert2.png');
    
                    if (!$saved) {
                        $msg_logo_right = "Failure to save the image: LOGO RIGHT. Try Again";
                    }
                }
            }

            $header_text = $request->getPost('header_text', null);
            $header_text_size = $request->getPost('header_text_font_size', null);

            if (trim($header_text) != '' || trim($header_text_size) != '') {
                $header_text = addslashes(trim($header_text));
                $stringWithoutBR = str_replace("\r\n", "<br />", $header_text);
                $this->certificationTable->insertTextHeader($stringWithoutBR, $header_text_size);
                $msg_header_text = "PDF Settings Saved Successfully.";
            }
            $container = new Container('alert');
            if ($msg_logo_left != '') {
                $container->alertMsg = $msg_logo_left;
            } elseif ($msg_logo_right != '') {
                $container->alertMsg = $msg_logo_right;
            } else {
                $container->alertMsg = "PDF Settings Saved Successfully.";
            }
            return $this->redirect()->toRoute('config');

            /*$headerText = $this->headerTextAction();
            $header_text_font_size = $this->certificationTable->SelectHeaderTextFontSize();
            return array(
                'msg_logo_left' => $msg_logo_left,
                'msg_logo_right' => $msg_logo_right,
                'msg_header_text' => $msg_header_text,
                'header_text' => $headerText['header_text'],
                'header_text_font_size' => $header_text_font_size
            );*/
        }
    }

    public function dashboardContentAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $this->commonService->updateDashboardContent($params);
            return $this->redirect()->toRoute('dashboard-content');
        } else {
            $result = $this->commonService->getConfigByGlobalName('dashboard-content');
            return new ViewModel(array(
                'result' => $result,
            ));
        }
    }
}
