<?php

namespace App\EventListener;

use App\Entity\Role;
use Lexik\Bundle\JWTAuthenticationBundle\Event\AuthenticationSuccessEvent;
use Symfony\Component\Security\Core\User\UserInterface;

class AuthenticationSuccessListener
{
    public function onAuthenticationSuccessResponse(AuthenticationSuccessEvent $event)
    {
        $data = $event->getData();
        $user = $event->getUser();

        /**
         * @var $user Role
         */
        if (!$user instanceof UserInterface) {
            return;
        }

        $data['authenticated'] = [
            'id' => $user->getId(),
            'username' => $user->getUsername(),
        ];

        $event->setData($data);
    }
}
