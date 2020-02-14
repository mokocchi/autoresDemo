<?php

namespace App\Controller\v1;

use App\Entity\Plano;
use App\Entity\Tarea;
use App\Form\PlanoType;
use App\Form\TareaType;
use App\Service\UploaderHelper;
use Exception;
use FOS\RestBundle\Context\Context;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;
/**
 * @Route("/tareas")
 */
class TareasController extends AbstractFOSRestController
{
    /**
     * Lists all Tarea.
     * @Rest\Get
     * @IsGranted("ROLE_ADMIN")
     * 
     * @return Response
     */
    public function getTareasAction()
    {
        $repository = $this->getDoctrine()->getRepository(Tarea::class);
        $tareas = $repository->findall();
        $view = $this->view($tareas);
        $context = new Context();
        $context->addGroup('autor');
        $view->setContext($context);
        return $this->handleView($view);
    }

    /**
     * Lists tareas for the current user
     * 
     * @Rest\Get("/user")
     * @IsGranted("ROLE_AUTOR")
     * 
     * @return Response
     */
    public function getActividadForUserAction()
    {
        $user = $this->getUser();
        $repository = $this->getDoctrine()->getRepository(Tarea::class);
        $tareas = $repository->findBy(["autor" => $user]);
        $view = $this->view($tareas);
        $context = new Context();
        $context->addGroup('autor');
        $view->setContext($context);
        return $this->handleView($view);
    }


    /**
     * Create Tarea.
     * @Rest\Post
     * @IsGranted("ROLE_AUTOR")
     *
     * @return Response
     */
    public function postTareaAction(Request $request)
    {
        $tarea = new Tarea();
        $form = $this->createForm(TareaType::class, $tarea);
        $data = json_decode($request->getContent(), true);
        $form->submit($data);
        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $em = $this->getDoctrine()->getManager();
                $tareaDb = $em->getRepository(Tarea::class)->findBy(["codigo" => $data["codigo"]]);
                if (!empty($tareaDb)) {
                    return $this->handleView($this->view($tareaDb[0], Response::HTTP_OK));
                }
                $tarea->setAutor($this->getUser());
                $em->persist($tarea);
                $em->flush();
                $view = $this->view($tarea, Response::HTTP_CREATED);
                $context = new Context();
                $context->addGroup('autor');
                $view->setContext($context);
                return $this->handleView($view);
            } catch (Exception $e) {
                return $this->handleView($this->view(["errors" => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
            }
        }
        return $this->handleView($this->view($form->getErrors(), Response::HTTP_INTERNAL_SERVER_ERROR));
    }


    /**
     * Shows a Tarea.
     * @Rest\Get("/{id}")
     * @IsGranted("ROLE_AUTOR")
     *
     * @return Response
     */
    public function showTareaAction($id)
    {
        $repository = $this->getDoctrine()->getRepository(Tarea::class);
        $tarea = $repository->find($id);
        if (is_null($tarea)) {
            return $this->handleView($this->view(['errors' => 'Objeto no encontrado'], Response::HTTP_NOT_FOUND));
        }
        $view = $this->view($tarea);
        $context = new Context();
        $context->addGroup('autor');
        $view->setContext($context);
        return $this->handleView($view);
    }



    /**
     * Update extra on Tarea.
     * @Rest\Post("/{id}/extra")
     * @IsGranted("ROLE_AUTOR")
     *
     * @return Response
     */
    public function updateExtraOnTareaAction(Request $request, $id)
    {
        $data = json_decode($request->getContent(), true);
        if (!array_key_exists("extra", $data)) {
            return $this->handleView($this->view(['errors' => 'Faltan campos en el request'], Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $em = $this->getDoctrine()->getManager();

        try {
            $extra = $data["extra"];
            $tarea = $em->getRepository(Tarea::class)->find($id);
            if (!is_null($extra) && !is_null($tarea)) {
                $tarea->setExtra($extra);
                $em->persist($tarea);
                $em->flush();
                return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_OK));
            } else {
                return $this->handleView($this->view(['errors' => 'Objeto no encontrado'], Response::HTTP_NOT_FOUND));
            }
        } catch (Exception $e) {
            return $this->handleView($this->view([$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }

    /**
     * Update plano on Tarea.
     * @Rest\Post("/{id}/plano")
     * @IsGranted("ROLE_AUTOR")
     *
     * @return Response
     */
    public function updateMapOnTareaAction(Request $request, $id, UploaderHelper $uploaderHelper, ValidatorInterface $validator)
    {
        
        if (!$request->files->has('plano')) {
            return $this->handleView($this->view(['errors' => 'No se encontró el archivo'], Response::HTTP_UNPROCESSABLE_ENTITY));
        }
        $plano = new Plano();
        $uploadedFile = $request->files->get('plano');
        $plano->setPlano($uploadedFile);

        $errors = $validator->validate($plano);

        if (count($errors) > 0) {
            return $this->handleView($this->view(['errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY));
        }
        try {
            $em = $this->getDoctrine()->getManager();
            $tarea = $em->getRepository(Tarea::class)->find($id);

            if (!is_null($tarea)) {

                $uploaderHelper->uploadPlano($uploadedFile, $tarea->getCodigo());

                return $this->handleView($this->view(['status' => 'ok'], Response::HTTP_OK));
            } else {
                return $this->handleView($this->view(['errors' => 'Objeto no encontrado'], Response::HTTP_NOT_FOUND));
            }
        } catch (Exception $e) {
            return $this->handleView($this->view([$e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR));
        }
    }
}
