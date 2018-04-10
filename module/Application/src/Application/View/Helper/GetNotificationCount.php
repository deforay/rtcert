<?php
namespace Application\View\Helper;

use Zend\ServiceManager\ServiceLocatorAwareInterface;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Helper\AbstractHelper;

class GetNotificationCount extends AbstractHelper implements ServiceLocatorAwareInterface{
    
    public function setServiceLocator(ServiceLocatorInterface $serviceLocator){
        $this->serviceLocator = $serviceLocator;  
        return $this;  
    }
    
    public function getServiceLocator(){  
        return $this->serviceLocator;  
    }
    public function __invoke(){
        $sm = $this->getServiceLocator()->getServiceLocator();
        $certificationDb = $sm->get('Certification\Model\CertificationTable');
      return $certificationDb->getNotificationCount();
    }
}