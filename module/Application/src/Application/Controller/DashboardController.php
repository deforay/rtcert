<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\Json\Json;

class DashboardController extends AbstractActionController
{
    public \Application\Service\DashboardService $dashboardService;

    public function __construct($dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function indexAction()
    {
        $quickStats = $this->dashboardService->getQuickStats();
        return new ViewModel(array(
            'quickStats' => $quickStats,
        ));
    }

    public function getCertificationPieChartDetailsAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->dashboardService->getCertificationPieChartResults($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result))
                ->setTerminal(true);
            return $viewModel;
        }
    }
    public function getCertifiedProvinceChartDetailsAction()
    {

        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->dashboardService->getCertifiedProvinceChartResults($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result))
                ->setTerminal(true);
            // \Zend\Debug\Debug::dump($result);die;
            return $viewModel;
        }
    }
    public function getCertifiedDistrictChartDetailsAction()
    {

        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->dashboardService->getCertifiedDistrictChartResults($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result))
                ->setTerminal(true);
            // \Zend\Debug\Debug::dump($result);die;
            return $viewModel;
        }
    }

    public function getCertificationBarChartDetailsAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->dashboardService->getCertificationBarChartResults($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result))
                ->setTerminal(true);
            return $viewModel;
        }
    }

    public function getCertificationMapDetailsAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->dashboardService->getCertificationMapResults($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result))
                ->setTerminal(true);
            return $viewModel;
        }
    }

    public function testersAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $result = $this->dashboardService->getTesters($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        } else {
            $frmSrc = $this->params()->fromRoute('id');
            return new ViewModel(array(
                'frmSrc' => $frmSrc
            ));
        }
    }

    public function getWrittenExamAverageAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->dashboardService->getWrittenExamAverageRadarResults($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result))
                ->setTerminal(true);
            return $viewModel;
        }
    }

    public function getPracticalExamAverageAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->dashboardService->getPracticalExamAverageBarResults($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result))
                ->setTerminal(true);
            return $viewModel;
        }
    }

    public function getVolumesPracticalWrittenDetailsAction()
    {
        /** @var \Laminas\Http\Request $request */
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $result = $this->dashboardService->getPracticalWrittenCountResults($params);
            $viewModel = new ViewModel();
            return $viewModel->setVariables(array('result' => $result))->setTerminal(true);
        }
    }
}
