<?php

namespace App\Security;

use App\Controller\JsonRequest;
use App\Entity\Role;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Security\Guard\PasswordAuthenticatedInterface;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class RoleAuthenticator extends AbstractGuardAuthenticator implements PasswordAuthenticatedInterface
{
    use JsonRequest;

    public const LOGIN_ROUTE = 'app_login';

    private $entityManager;
    private $urlGenerator;
    private $csrfTokenManager;
    private $passwordEncoder;

    private SerializerInterface $serializer;

    private ValidatorInterface $validator;

    public function __construct(
        EntityManagerInterface $entityManager,
        UrlGeneratorInterface $urlGenerator,
        CsrfTokenManagerInterface $csrfTokenManager,
        UserPasswordEncoderInterface $passwordEncoder,
        SerializerInterface $serializer,
        ValidatorInterface $validator
    ) {
        $this->entityManager = $entityManager;
        $this->urlGenerator = $urlGenerator;
        $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
        $this->serializer = $serializer;
        $this->validator = $validator;
    }

    public function supports(Request $request)
    {
        $route = $request->attributes->get('_route');
        return self::LOGIN_ROUTE === $route && ($request->isMethod('POST') || $request->query->count());
    }

    public function getCredentials(Request $request)
    {
        $credentials = [
            'username' => $request->request->get('username', $request->query->get('username', '')),
            'password' => $request->request->get('password', $request->query->get('password', '')),
        ];

        $errors_username = $this->validator->validate($credentials['username'], [
            new Assert\Regex(['pattern' => Role::NAME_REGEX]),
            new Assert\Length(['min' => 4, 'max' => 32]),
        ]);

        $errors_password = $this->validator->validate($credentials['password'], [
            new Assert\Length(['min' => 6]),
        ]);

        if ($errors_username->count() || $errors_password->count()) {
            throw new BadCredentialsException();
        }

        $request->getSession()->set(
            Security::LAST_USERNAME,
            $credentials['username']
        );

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {

        $user = $this->entityManager->getRepository(Role::class)->findOneBy(['username' => $credentials['username']]);

        if (!$user) {
            throw new BadCredentialsException();
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function getPassword($credentials): ?string
    {
        return $credentials['password'];
    }

    public function start(Request $request, AuthenticationException $authException = null)
    {
        return $this->json([
            'error' => 'Authentication Required',
        ], 401);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        return $this->json([
            'error' => 'Authentication Failed',
            'message' => $exception->getMessage(),
            'message_key' => $exception->getMessageKey(),
        ], 401);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        return new RedirectResponse($this->getLoginUrl());
    }

    public function supportsRememberMe()
    {
        return true;
    }

    protected function json($data, int $status = 200, array $headers = [], array $context = []): JsonResponse
    {
        $json = $this->serializer->serialize($data, 'json', array_merge([
            'json_encode_options' => JsonResponse::DEFAULT_ENCODING_OPTIONS,
        ], $context));

        return new JsonResponse($json, $status, $headers, true);
    }

    protected function getLoginUrl()
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
