<?php

namespace App\Domain\Service;

use App\Entity\Role;
use App\Repository\RoleRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Encoder\JWTEncoderInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;

class AuthenticatedRoleFinder
{
    private ?Request $request;

    private JWTEncoderInterface $jwtEncoder;

    public function __construct(
        RequestStack $requestStack,
        JWTEncoderInterface $JWTEncoder,
        RoleRepository $roleRepository
    ) {
        $this->request = $requestStack->getCurrentRequest();
        $this->jwtEncoder = $JWTEncoder;
        $this->roleRepository = $roleRepository;
    }

    public function getToken(): array
    {
        $auth_token = $this->request->headers->get('authorization', '');
        $token = '';
        if (strlen($auth_token) > 32) {
            $token = substr($auth_token, 7);
        }

        return $this->jwtEncoder->decode($token);
    }

    public function getRole(): ?Role
    {
        $decoded = $this->getToken();
        $auth_user_id = $decoded['user_id'];

        return $this->roleRepository->findOneBy(['id' => $auth_user_id]);
    }
}
