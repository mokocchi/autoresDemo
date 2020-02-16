<?php

namespace App\Controller;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Psr\Log\LoggerInterface;

class BaseController extends AbstractFOSRestController{
    protected $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;        
    }

    protected function getViewWithGroups($object, $group){
        $view = $this->view($object);
        return $this->setGroupToView($view,$group);
    }

    protected function setGroupToView($view, $group) {
        $context = new Context();
        $context->addGroup($group);
        $view->setContext($context);
        return $view;
    }
}