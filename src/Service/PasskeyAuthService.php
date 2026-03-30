<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\WebauthnCredential;
use App\Repository\WebauthnCredentialRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class PasskeyAuthService
{
    private const RP_ID   = 'localhost';
    private const RP_NAME = 'EventBook';
    private const CHALLENGE_BYTES = 32;
    private const SESSION_CHALLENGE_KEY = 'webauthn_challenge';

    public function __construct(
        private readonly EntityManagerInterface        $em,
        private readonly WebauthnCredentialRepository $credentialRepository,
        private readonly RequestStack                  $requestStack,
    ) {}

    public function generateRegistrationOptions(User $user): array
    {
        $challenge = $this->generateChallenge();

        $this->getSession()->set(
            self::SESSION_CHALLENGE_KEY,
            base64_encode($challenge)
        );

        $excludeCredentials = array_map(
            fn(WebauthnCredential $c) => [
                'id'   => $c->getCredentialId(),
                'type' => 'public-key',
            ],
            $this->credentialRepository->findByUser($user)
        );

        return [
            'rp' => [
                'id'   => self::RP_ID,
                'name' => self::RP_NAME,
            ],
            'user' => [
                'id'          => $this->base64urlEncode((string) $user->getId()),
                'name'        => $user->getUsername(),
                'displayName' => $user->getUsername(),
            ],
            'challenge'         => $this->base64urlEncode($challenge),
            'pubKeyCredParams' => [
                ['type' => 'public-key', 'alg' => -7],
                ['type' => 'public-key', 'alg' => -257],
            ],
            'timeout'         => 60000,
            'excludeCredentials' => $excludeCredentials,
            'authenticatorSelection' => [
                'userVerification' => 'preferred',
                'residentKey'      => 'preferred',
            ],
            'attestation' => 'none',
        ];
    }

    public function verifyRegistrationResponse(User $user, array $body): WebauthnCredential
    {
        $credentialId = $body['id']       ?? null;
        $type         = $body['type']     ?? null;
        $response     = $body['response'] ?? [];
        $deviceName   = $body['deviceName'] ?? 'Passkey';

        if (!$credentialId || $type !== 'public-key') {
            throw new \RuntimeException('Champ credential manquant ou type invalide.');
        }

        $clientDataJSON = $response['clientDataJSON'] ?? null;
        if (!$clientDataJSON) {
            throw new \RuntimeException('clientDataJSON manquant.');
        }

        $clientData = json_decode(
            base64_decode($this->base64urlToBase64($clientDataJSON)),
            true
        );

        if (($clientData['type'] ?? '') !== 'webauthn.create') {
            throw new \RuntimeException('Type WebAuthn invalide pour l\'enregistrement.');
        }

        $storedChallenge = $this->getSession()->get(self::SESSION_CHALLENGE_KEY);
        $receivedChallenge = $clientData['challenge'] ?? '';

        if (!$storedChallenge || $receivedChallenge !== $this->base64urlEncode(base64_decode($storedChallenge))) {
            throw new \RuntimeException('Challenge WebAuthn invalide ou expiré.');
        }

        $this->getSession()->remove(self::SESSION_CHALLENGE_KEY);

        $existing = $this->credentialRepository->findByCredentialId($credentialId);
        if ($existing) {
            throw new \RuntimeException('Ce credential est déjà enregistré.');
        }

        $publicKey = $response['attestationObject'] ?? $credentialId;

        $credential = new WebauthnCredential(
            user:         $user,
            credentialId: $credentialId,
            publicKey:    $publicKey,
            deviceName:   $deviceName,
            signCount:    0,
        );

        $this->em->persist($credential);
        $this->em->flush();

        return $credential;
    }

    public function generateAuthenticationOptions(): array
    {
        $challenge = $this->generateChallenge();

        $this->getSession()->set(
            self::SESSION_CHALLENGE_KEY,
            base64_encode($challenge)
        );

        return [
            'challenge'         => $this->base64urlEncode($challenge),
            'timeout'           => 60000,
            'rpId'               => self::RP_ID,
            'userVerification' => 'preferred',
        ];
    }

    public function verifyAuthenticationResponse(array $body): User
    {
        $credentialId = $body['id']       ?? null;
        $type         = $body['type']     ?? null;
        $response     = $body['response'] ?? [];

        if (!$credentialId || $type !== 'public-key') {
            throw new \RuntimeException('Credential invalide.');
        }

        $credential = $this->credentialRepository->findByCredentialId($credentialId);
        if (!$credential) {
            throw new \RuntimeException('Passkey non reconnue. Enregistrez d\'abord votre appareil.');
        }

        $clientDataJSON = $response['clientDataJSON'] ?? null;
        if (!$clientDataJSON) {
            throw new \RuntimeException('clientDataJSON manquant.');
        }

        $clientData = json_decode(
            base64_decode($this->base64urlToBase64($clientDataJSON)),
            true
        );

        if (($clientData['type'] ?? '') !== 'webauthn.get') {
            throw new \RuntimeException('Type WebAuthn invalide pour l\'authentification.');
        }

        $storedChallenge   = $this->getSession()->get(self::SESSION_CHALLENGE_KEY);
        $receivedChallenge = $clientData['challenge'] ?? '';

        if (!$storedChallenge || $receivedChallenge !== $this->base64urlEncode(base64_decode($storedChallenge))) {
            throw new \RuntimeException('Challenge WebAuthn invalide ou expiré.');
        }

        $this->getSession()->remove(self::SESSION_CHALLENGE_KEY);

        $newSignCount = (int) ($body['signCount'] ?? $credential->getSignCount());

        if ($newSignCount < $credential->getSignCount()) {
            throw new \RuntimeException('Sign count invalide — possible tentative de rejeu.');
        }

        $credential->recordUsage($newSignCount);
        $this->em->flush();

        return $credential->getUser();
    }

    private function generateChallenge(): string
    {
        return random_bytes(self::CHALLENGE_BYTES);
    }

    private function base64urlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64urlToBase64(string $base64url): string
    {
        $base64 = strtr($base64url, '-_', '+/');
        $pad = strlen($base64) % 4;
        if ($pad) {
            $base64 .= str_repeat('=', 4 - $pad);
        }
        return $base64;
    }

    private function getSession(): \Symfony\Component\HttpFoundation\Session\SessionInterface
    {
        return $this->requestStack->getSession();
    }
    public function listCredentials(User $user): array
{
    return $this->credentialRepository->findByUser($user);
}

public function findCredentialForUser(User $user, int $id): ?WebauthnCredential
{
    return $this->credentialRepository->findByIdAndUser($id, $user);
}
}