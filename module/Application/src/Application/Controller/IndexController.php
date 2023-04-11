<?php

namespace Application\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{

    public \Application\Service\DashboardService $dashboardService;

    public function __construct($dashboardService)
    {
        $this->dashboardService = $dashboardService;
    }

    public function indexAction()
    {

        $quickStats = $this->dashboardService->getQuickStats();
        $viewModel = new ViewModel(array(
            'quickStats' => $quickStats,
        ));

        $viewModel->setTerminal(true);
        return $viewModel;
    }
}
