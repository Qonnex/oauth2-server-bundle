<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Service\CredentialsRevoker;

use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Document\AccessToken;
use League\Bundle\OAuth2ServerBundle\Document\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Document\RefreshToken;
use League\Bundle\OAuth2ServerBundle\Service\CredentialsRevokerInterface;
use Symfony\Component\Security\Core\User\UserInterface;

final class DoctrineCredentialsRevokerDocument implements CredentialsRevokerInterface
{
    private $documentManager;
    private $clientManager;

    public function __construct(DocumentManager $documentManager, ClientManagerInterface $clientManager)
    {
        $this->documentManager = $documentManager;
        $this->clientManager = $clientManager;
    }

    public function revokeCredentialsForUser(UserInterface $user): void
    {
        $userIdentifier = $user->getUserIdentifier();

        $accessTokens = $this->documentManager->getRepository(AccessToken::class)
            ->findBy(['userIdentifier' => $userIdentifier]);

        foreach ($accessTokens as $token) {
            $token->setRevoked(true);
            $this->documentManager->persist($token);
        }

        $refreshTokens = $this->documentManager->getRepository(RefreshToken::class)
            ->findBy(['userIdentifier' => $userIdentifier]);

        foreach ($refreshTokens as $token) {
            $token->setRevoked(true);
            $this->documentManager->persist($token);
        }

        $authCodes = $this->documentManager->getRepository(AuthorizationCode::class)
            ->findBy(['userIdentifier' => $userIdentifier]);

        foreach ($authCodes as $code) {
            $code->setRevoked(true);
            $this->documentManager->persist($code);
        }

        $this->documentManager->flush();
    }

    public function revokeCredentialsForClient($client): void
    {
        $clientId = $client->getIdentifier();

        $accessTokens = $this->documentManager->getRepository(AccessToken::class)
            ->findBy(['client' => $clientId]);

        foreach ($accessTokens as $token) {
            $token->setRevoked(true);
            $this->documentManager->persist($token);
        }

        $refreshTokens = $this->documentManager->getRepository(RefreshToken::class)
            ->findBy(['client' => $clientId]);

        foreach ($refreshTokens as $token) {
            $token->setRevoked(true);
            $this->documentManager->persist($token);
        }

        $authCodes = $this->documentManager->getRepository(AuthorizationCode::class)
            ->findBy(['client' => $clientId]);

        foreach ($authCodes as $code) {
            $code->setRevoked(true);
            $this->documentManager->persist($code);
        }

        $this->documentManager->flush();
    }
}
