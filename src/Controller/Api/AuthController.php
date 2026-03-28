<?php

namespace App\Controller\Api;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/api/auth', name: 'api_auth_')]
class AuthController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface      $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
        private readonly JWTTokenManagerInterface    $jwtManager,
        private readonly ValidatorInterface          $validator,
    ) {}

    /**
     * POST /api/auth/register — Inscription utilisateur
     */
    #[Route('/register', name: 'register', methods: ['POST'])]
    public function register(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $user = new User();
        $user->setUsername($data['username'] ?? '');
        $user->setEmail($data['email'] ?? '');

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            $formatted = [];
            foreach ($errors as $error) {
                $formatted[$error->getPropertyPath()] = $error->getMessage();
            }
            return $this->json(['success' => false, 'errors' => $formatted], 422);
        }

        if (empty($data['password']) || strlen($data['password']) < 6) {
            return $this->json([
                'success' => false,
                'errors'  => ['password' => 'Le mot de passe doit contenir au moins 6 caractères.'],
            ], 422);
        }

        $hashedPassword = $this->passwordHasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        $this->em->persist($user);
        $this->em->flush();

        $token = $this->jwtManager->create($user);

        return $this->json([
            'success' => true,
            'message' => 'Compte créé avec succès.',
            'token'   => $token,
            'user'    => [
                'id'       => $user->getId(),
                'username' => $user->getUsername(),
                'email'    => $user->getEmail(),
            ],
        ], Response::HTTP_CREATED);
    }

    /**
     * POST /api/auth/login — Connexion JWT
     * La vérification du mot de passe est gérée par lexik/jwt-authentication-bundle
     * Ce endpoint retourne les infos utilisateur après authentification réussie
     */
    #[Route('/login', name: 'login', methods: ['POST'])]
    public function login(#[CurrentUser] ?User $user): JsonResponse
    {
        if (!$user) {
            return $this->json([
                'success' => false,
                'message' => 'Identifiants incorrects.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $token = $this->jwtManager->create($user);

        return $this->json([
            'success' => true,
            'token'   => $token,
            'user'    => [
                'id'       => $user->getId(),
                'username' => $user->getUsername(),
                'email'    => $user->getEmail(),
                'roles'    => $user->getRoles(),
            ],
        ]);
    }

    /**
     * GET /api/auth/me — Profil de l'utilisateur connecté
     */
    #[Route('/me', name: 'me', methods: ['GET'])]
    public function me(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['success' => false, 'message' => 'Non authentifié.'], 401);
        }

        return $this->json([
            'success' => true,
            'user'    => [
                'id'              => $user->getId(),
                'username'        => $user->getUsername(),
                'email'           => $user->getEmail(),
                'roles'           => $user->getRoles(),
                'hasPasskey'      => !empty($user->getPasskeyCredentials()),
            ],
        ]);
    }

    // ─────────────────────────────────────────────
    // PASSKEYS (WebAuthn)
    // ─────────────────────────────────────────────

    /**
     * POST /api/auth/passkey/register/options — Démarrer l'enregistrement d'une passkey
     */
    #[Route('/passkey/register/options', name: 'passkey_register_options', methods: ['POST'])]
    public function passkeyRegisterOptions(): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['success' => false], 401);
        }

        // Options de création de credential WebAuthn
        // En production, utiliser le service WebAuthnManager du bundle web-auth/webauthn-bundle
        $options = [
            'rp' => [
                'name' => 'EventBook',
                'id'   => 'localhost',
            ],
            'user' => [
                'id'          => base64_encode((string) $user->getId()),
                'name'        => $user->getUsername(),
                'displayName' => $user->getUsername(),
            ],
            'challenge'        => base64_encode(random_bytes(32)),
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],   // ES256
                ['type' => 'public-key', 'alg' => -257],  // RS256
            ],
            'timeout'         => 60000,
            'authenticatorSelection' => [
                'userVerification' => 'preferred',
                'residentKey'      => 'preferred',
            ],
            'attestation' => 'none',
        ];

        return $this->json(['success' => true, 'options' => $options]);
    }

    /**
     * POST /api/auth/passkey/register/verify — Finaliser l'enregistrement d'une passkey
     */
    #[Route('/passkey/register/verify', name: 'passkey_register_verify', methods: ['POST'])]
    public function passkeyRegisterVerify(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $this->getUser();

        if (!$user) {
            return $this->json(['success' => false], 401);
        }

        $data = json_decode($request->getContent(), true);

        // En production : vérifier la réponse WebAuthn avec web-auth/webauthn-bundle
        // Ici on stocke le credential brut pour la démo
        $credentials   = $user->getPasskeyCredentials() ?? [];
        $credentials[] = [
            'id'        => $data['id'] ?? null,
            'rawId'     => $data['rawId'] ?? null,
            'type'      => $data['type'] ?? 'public-key',
            'createdAt' => (new \DateTime())->format('c'),
        ];
        $user->setPasskeyCredentials($credentials);
        $this->em->flush();

        return $this->json([
            'success' => true,
            'message' => 'Passkey enregistrée avec succès.',
        ]);
    }
}