<?php

namespace App\Controller;

use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;

class BaseController extends AbstractFOSRestController{
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