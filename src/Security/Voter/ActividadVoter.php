<?php

namespace App\Security\Voter;

use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Entity\Actividad;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class ActividadVoter extends Voter
{
    public const ACCESS = "ACTIVIDAD_ACCESS";
    public const OWN = "ACTIVIDAD_OWN";
    private $security;
    public function __construct(Security $security)
    {
        $this->security = $security;
    }
    
    protected function supports($attribute, $subject)
    {
        return in_array($attribute, ['ACTIVIDAD_ACCESS', 'ACTIVIDAD_OWN'])
            && $subject instanceof Actividad;
    }

    protected function voteOnAttribute($attribute, $subject, TokenInterface $token)
    {
        $user = $token->getUser();
        // if the user is anonymous, do not grant access
        if (!$user instanceof UserInterface) {
            return false;
        }

        if(!$this->security->isGranted("ROLE_AUTOR")) {
            return false;
        }

        /**@var Actividad $subject */
        switch ($attribute) {
            case 'ACTIVIDAD_ACCESS':
                if ($subject->getAutor() == $user){
                    return true;
                }
                if($subject->getEstado()->getNombre() == "Público") {
                    return true;
                }
                throw new ApiProblemException(
                    new ApiProblem(Response::HTTP_FORBIDDEN, "La actividad es privada o no pertenece al usuario actual", "No se puede acceder a la actividad")
                );
                break;
            case 'ACTIVIDAD_OWN':
                if ($subject->getAutor() == $user) {
                    return true;
                }
                throw new ApiProblemException(
                    new ApiProblem(Response::HTTP_FORBIDDEN, "La actividad no pertenece al usuario actual", "No se puede acceder a la actividad"),
                );
                break;
        }

        return false;
    }
}
