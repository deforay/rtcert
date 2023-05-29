<?php

namespace Application\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class SendTestAlertMail extends Command
{

    public \Certification\Model\ProviderTable $providerTable;

    public function __construct($providerTable)
    {
        $this->providerTable = $providerTable;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->providerTable->sendAutoTestLink();
        return 1;
    }
}