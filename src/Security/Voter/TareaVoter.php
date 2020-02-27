<?php

namespace App\Security\Voter;

use App\Api\ApiProblem;
use App\Api\ApiProblemException;
use App\Entity\Tarea;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;

class TareaVoter extends Voter
{
    public const ACCESS = "TAREA_ACCESS";
    public const OWN = "TAREA_OWN";
    private $security;
    public function __construct(Security $security)
    {
        $this->security = $security;
    }
    protected function supports($attribute, $subject)
    {
        return in_array($attribute, ['TAREA_ACCESS', 'TAREA_OWN'])
            && $subject instanceof Tarea;
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

        // ... (check conditions and return true to grant permission) ...
        switch ($attribute) {
            case 'TAREA_ACCESS':
                if ($subject->getAutor() == $user){
                    return true;
                }
                if($subject->getEstado()->getNombre() == "Público") {
                    return true;
                }
                throw new ApiProblemException(
                    new ApiProblem(Response::HTTP_FORBIDDEN, "La tarea es privada o no pertenece al usuario actual", "No se puede acceder a la tarea")
                );
                break;
            case 'TAREA_OWN':
                if ($subject->getAutor() == $user) {
                    return true;
                }
                throw new ApiProblemException(
                    new ApiProblem(Response::HTTP_FORBIDDEN, "La tarea no pertenece al usuario actual", "No se puede acceder a la tarea"),
                );
                break;
        }

        return false;
    }
}
