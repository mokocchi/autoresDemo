<?php

namespace App\Serializer;

use App\Annotation\Link;
use Doctrine\Common\Annotations\Reader;
use JMS\Serializer\EventDispatcher\EventSubscriberInterface;
use JMS\Serializer\EventDispatcher\ObjectEvent;
use JMS\Serializer\Metadata\StaticPropertyMetadata;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

class LinkSerializationSubscriber implements EventSubscriberInterface
{
    private $router;
    private $annotationReader;
    public function __construct(RouterInterface $router, Reader $annotationReader)
    {
        $this->router = $router;
        $this->annotationReader = $annotationReader;
        $this->expressionLanguage = new ExpressionLanguage();
    }

    public static function getSubscribedEvents()
    {
        return [
            [
                'event' => 'serializer.post_serialize',
                'method' => 'onPostSerialize',
                'format' => 'json',
                'class' => 'App\Entity\Actividad'
            ]
        ];
    }

    public function onPostSerialize(ObjectEvent $event)
    {
        /** @var JsonSerializationVisitor $visitor */
        $visitor = $event->getVisitor();
        /** @var Actividad $actividad */
        $object = $event->getObject();
        $annotations = $this->annotationReader
            ->getClassAnnotations(new \ReflectionObject($object));
        $links = [];
        foreach ($annotations as $annotation) {
            if ($annotation instanceof Link) {
                $uri = $this->router->generate(
                    $annotation->route,
                    $this->resolveParams($annotation->params, $object)
                );
                $links[$annotation->name] = $uri;
            }
        }
        $visitor->visitProperty(new StaticPropertyMetadata('', '_links', null), $links);
    }

    private function resolveParams(array $params, $object)
    {
        foreach ($params as $key => $param) {
            $params[$key] = $this->expressionLanguage
                ->evaluate($param, array('object' => $object));
        }
        return $params;
    }
}
