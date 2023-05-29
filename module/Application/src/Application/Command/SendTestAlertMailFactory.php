<?php

namespace Application\Command;

use Laminas\ServiceManager\Factory\FactoryInterface;
use Interop\Container\ContainerInterface;
use Application\Command\SendTestAlertMail;

class SendTestAlertMailFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $providerTable = $container->get('Certification\Model\ProviderTable');
        return new SendTestAlertMail($providerTable);
    }
}