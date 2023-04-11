<?php

namespace Application\Controller;

use Laminas\Config\Config;
use Laminas\Json\Json;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class UsersController extends AbstractActionController
{
    public \Application\Service\UserService $userService;
    public \Application\Service\RoleService $roleService;
    public \Application\Service\CommonService $commonService;

    public function __construct($userService, $commonService, $roleService)
    {
        $this->userService = $userService;
        $this->commonService = $commonService;
        $this->roleService = $roleService;
    }

    public function indexAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->userService->getAllUsers($params);
            return $this->getResponse()->setContent(Json::encode($result));
        }
    }

    public function addAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $this->userService->addUser($params);
            return $this->redirect()->toRoute("users");
        }
        $roleResult = $this->roleService->getAllActiveRoles();
        $countryResult = $this->commonService->getAllActiveCountries();
        $provinceResult = $this->commonService->getAllProvinces();
        $districtResult = $this->commonService->getAllDistricts();

        return new ViewModel(
            array(
                'roleResults' => $roleResult,
                'countryResult' => $countryResult,
                'provinceResult' => $provinceResult,
                'districtResult' => $districtResult
            )
        );
    }

    public function editAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->userService->updateUser($params);
            return $this->redirect()->toRoute("users");
        } else {
            $id = base64_decode($this->params()->fromRoute('id'));
            $result = $this->userService->getUser($id);
            $roleResult = $this->roleService->getAllActiveRoles();
            $countryResult = $this->commonService->getAllActiveCountries();
            $selectedCountries = array();
            if (isset($result['userCountries']) && count($result['userCountries']) > 0) {
                foreach ($result['userCountries'] as $country) {
                    $selectedCountries[] = $country['country_id'];
                }
            } else if (isset($result['selectedCountries']) && count($result['selectedCountries']) > 0) {
                $selectedCountries = $result['selectedCountries'];
            }
            $provinceResult = $this->commonService->getAllProvinces($selectedCountries);
            $selectedProvinces = [];
            if (isset($result['userProvinces']) && count($result['userProvinces']) > 0) {
                foreach ($result['userProvinces'] as $province) {
                    $selectedProvinces[] = $province['location_id'];
                }
            } else if (isset($result['selectedProvinces']) && count($result['selectedProvinces']) > 0) {
                $selectedProvinces = $result['selectedProvinces'];
            }
            $districtResult = $this->commonService->getAllDistricts($selectedProvinces);
            return new ViewModel(array(
                'result' => $result,
                'roleResults' => $roleResult,
                'countryResult' => $countryResult,
                'provinceResult' => $provinceResult,
                'districtResult' => $districtResult
            ));
        }
    }
}
