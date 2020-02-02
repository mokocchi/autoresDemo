<?php

namespace App\Controller\auth;

use App\Entity\Usuario;
use Exception;
use Swagger\Annotations as SWG;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use FOS\RestBundle\Controller\Annotations as Rest;
use Google_Client;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/register")
 */
class RegistroController extends AbstractFOSRestController
{
    /**
     * @Rest\Post
     *
     * @SWG\Response(
     *     response=201,
     *     description="User was successfully registered"
     * )
     * 
     * @SWG\Response(
     *     response=422,
     *     description="There was a problem with the request"
     * )
     *
     * @SWG\Response(
     *     response=500,
     *     description="User was not successfully registered"
     * )
     *
     * @SWG\Parameter(
     *     name="username",
     *     in="body",
     *     type="string",
     *     description="The username",
     *     schema={}
     * )
     *
     * @SWG\Parameter(
     *     name="email",
     *     in="body",
     *     type="string",
     *     description="The email",
     *     schema={}
     * )
     *
     * @SWG\Parameter(
     *     name="nombre",
     *     in="body",
     *     type="string",
     *     description="The given name",
     *     schema={}
     * )
     *
     * @SWG\Parameter(
     *     name="apellido",
     *     in="body",
     *     type="string",
     *     description="The last name",
     *     schema={}
     * )
     * 
     * @SWG\Parameter(
     *     name="id_token",
     *     in="body",
     *     type="string",
     *     description="The google id token",
     *     schema={}
     * )
     *
     * @SWG\Tag(name="User")
     */
    public function registerAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();

        try {
            $data = json_decode($request->getContent(), true);

            $username = $data['username'];
            if (!array_key_exists("username", $data)) {
                return $this->handleView($this->view(['errors' => 'Faltan campos en el request: username'], Response::HTTP_UNPROCESSABLE_ENTITY));
            }
            $email = $data['email'];
            if (!array_key_exists("email", $data)) {
                return $this->handleView($this->view(['errors' => 'Faltan campos en el request: email'], Response::HTTP_UNPROCESSABLE_ENTITY));
            }
            $nombre = $data['nombre'];
            if (!array_key_exists("nombre", $data)) {
                return $this->handleView($this->view(['errors' => 'Faltan campos en el request: nombre'], Response::HTTP_UNPROCESSABLE_ENTITY));
            }
            $apellido = $data['apellido'];
            if (!array_key_exists("apellido", $data)) {
                return $this->handleView($this->view(['errors' => 'Faltan campos en el request: apellido'], Response::HTTP_UNPROCESSABLE_ENTITY));
            }
            $id_token = $data['id_token'];
            if (!array_key_exists("id_token", $data)) {
                return $this->handleView($this->view(['errors' => 'Faltan campos en el request: id_token'], Response::HTTP_UNPROCESSABLE_ENTITY));
            }

            $user = new Usuario();
            $user->setUsername($username);
            $user->setEmail($email);
            $user->setNombre($nombre);
            $user->setApellido($apellido);
            //exchange id_token with google_id
            $client = new Google_Client(['client_id' => $_ENV["GOOGLE_CLIENT_ID"]]);  // Specify the CLIENT_ID of the app that accesses the backend
            $payload = $client->verifyIdToken($id_token);
            if ($payload) {
                $userid = $payload['sub'];
                $user->setGoogleid($userid);
            } else {
                return $this->handleView($this->view(['errors' => 'Ocurrió un error al crear el usuario'], Response::HTTP_INTERNAL_SERVER_ERROR));
            }
            $users = $this->getDoctrine()->getRepository(Usuario::class)->findBy(["username" => $username]);
            if(count($users) != 0) {
                return $this->handleView($this->view(['errors' => 'El nombre de usuario ya existe'], Response::HTTP_UNPROCESSABLE_ENTITY));
            }
            $em->persist($user);
            $em->flush();
        } catch (Exception $ex) {
            return $this->handleView($this->view(['errors' => 'Ocurrió un error al crear el usuario'], Response::HTTP_INTERNAL_SERVER_ERROR));
        }

        return $this->handleView($this->view($user));
    }
}
