<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2015 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Application\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Json\Json;

class DashboardController extends AbstractActionController
{
    public function indexAction(){
        $dashService = $this->getServiceLocator()->get('DashboardService');
        $quickStats = $dashService->getQuickStats();
        return new ViewModel(array(
            'quickStats' => $quickStats,
        ));
    }
    
    public function getCertificationPieChartDetailsAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dashService = $this->getServiceLocator()->get('DashboardService');
            $result = $dashService->getCertificationPieChartResults($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result))
                        ->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function getCertificationBarChartDetailsAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dashService = $this->getServiceLocator()->get('DashboardService');
            $result = $dashService->getCertificationBarChartResults($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result))
                        ->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function getCertificationMapDetailsAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dashService = $this->getServiceLocator()->get('DashboardService');
            $result = $dashService->getCertificationMapResults($params);
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result))
                        ->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function testersAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $parameters = $request->getPost();
            $dashService = $this->getServiceLocator()->get('DashboardService');
            $result = $dashService->getTesters($parameters);
            return $this->getResponse()->setContent(Json::encode($result));
        }else{
            $frmSrc = $this->params()->fromRoute('id');
            return new ViewModel(array(
                'frmSrc' => $frmSrc
            ));
        }
    }

    public function getWrittenExamAverageAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dashService = $this->getServiceLocator()->get('DashboardService');
            $result = $dashService->getWrittenExamAverageRadarResults($params);
            //\Zend\Debug\Debug::dump($result);die;
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result))
                        ->setTerminal(true);
            return $viewModel;
        }
    }

    public function getPracticalExamAverageAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dashService = $this->getServiceLocator()->get('DashboardService');
            $result = $dashService->getPracticalExamAverageBarResults($params);
            //\Zend\Debug\Debug::dump($result);die;
            $viewModel = new ViewModel();
            $viewModel->setVariables(array('result' => $result))
                        ->setTerminal(true);
            return $viewModel;
        }
    }
    
    public function getVolumesPracticalWrittenDetailsAction(){
        $request = $this->getRequest();
        if ($request->isPost()) {
            $params = $request->getPost();
            $dashService = $this->getServiceLocator()->get('DashboardService');
            $result = $dashService->getPracticalWrittenCountResults($params);
            // \Zend\Debug\Debug::dump($result);die;
            $viewModel = new ViewModel();
            return $viewModel->setVariables(array('result' => $result))->setTerminal(true);
        }
    }
    
}
