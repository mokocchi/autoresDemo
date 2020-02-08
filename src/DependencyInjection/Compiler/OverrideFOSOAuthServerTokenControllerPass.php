<?php

namespace App\DependencyInjection\Compiler;

use App\Controller\auth\TokenController as AuthTokenController;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OverrideFOSOAuthServerTokenControllerPass implements CompilerPassInterface
{
  public function process(ContainerBuilder $container)
  {
    $definition = $container->getDefinition('FOS\OAuthServerBundle\Controller\TokenController');
    $definition->setClass(AuthTokenController::class);
    $definition->addArgument(new Reference('doctrine.orm.entity_manager'));
  }
}