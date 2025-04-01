<?php

namespace App\Controller;

use App\Dto\RegistrationRequest;
use App\Entity\User;
use App\Service\ValidationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

#[Route('/api')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly ValidationService $validationService,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly EntityManagerInterface $entityManager
    ) {}

    #[Route('/login', name: 'api_login', methods: ['POST'])]
    public function login(
        Request $request,
        JWTTokenManagerInterface $jwtManager
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['email']) || !isset($data['password'])) {
            return new JsonResponse(['error' => 'Missing credentials'], 400);
        }

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $data['email']]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $data['password'])) {
            return new JsonResponse(['error' => 'Invalid credentials'], 401);
        }

        $token = $jwtManager->create($user);

        return new JsonResponse([
            'token' => $token,
            'user' => [
                'id' => $user->getId(),
                'email' => $user->getEmail()
            ]
        ]);
    }

    #[Route('/register', name: 'app_register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true) ?? [];
        $registrationRequest = RegistrationRequest::fromRequest($data);

        if ($response = $this->validationService->validate($registrationRequest)) {
            return $response;
        }

        $user = new User();
        $user->setEmail($registrationRequest->email)
            ->setFirstName($registrationRequest->firstName)
            ->setLastName($registrationRequest->lastName)
            ->setPassword(
                $this->passwordHasher->hashPassword(
                    $user,
                    $registrationRequest->password
                )
            );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $this->json($user, 201, [], ['groups' => [User::GROUP_READ]]);
    }
}