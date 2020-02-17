<?php

namespace App\DependencyInjection\Compiler;

use App\Controller\auth\TokenController as AuthTokenController;
use App\Security\OAuthEntryPoint;
use FOS\OAuthServerBundle\Controller\AuthorizeController as ControllerAuthorizeController;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class OverrideFOSOAuthServerTokenControllerPass implements CompilerPassInterface
{
  public function process(ContainerBuilder $container)
  {
    $container->removeDefinition('fos_oauth_server.controller.authorize');
    $definition = $container->getDefinition('FOS\OAuthServerBundle\Controller\TokenController');
    $definition->setClass(AuthTokenController::class);
    $definition->addArgument(new Reference('doctrine.orm.entity_manager'));
    $definition->addArgument(new Reference('fos_oauth_server.client_manager'));

    $definition = $container->getDefinition('fos_oauth_server.security.entry_point');
    $definition->setClass(OAuthEntryPoint::class);
    $definition->addArgument(new Reference('jms_serializer'));
  }
}