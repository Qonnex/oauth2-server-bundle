<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\Doctrine;

use Doctrine\Persistence\ObjectManager;
use League\Bundle\OAuth2ServerBundle\Manager\RefreshTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\RefreshToken;
use League\Bundle\OAuth2ServerBundle\Model\RefreshTokenInterface;

final class RefreshTokenManager implements RefreshTokenManagerInterface
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function find(string $identifier): ?RefreshTokenInterface
    {
        return $this->objectManager->find(RefreshToken::class, $identifier);
    }

    public function save(RefreshTokenInterface $refreshToken): void
    {
        $this->objectManager->persist($refreshToken);
        $this->objectManager->flush();
    }

    public function clearExpired(): int
    {
        // ORM-specific: use DQL
        if (method_exists($this->objectManager, 'createQueryBuilder')) {
            /** @var int */
            return $this->objectManager->createQueryBuilder()
                ->delete(RefreshToken::class, 'rt')
                ->where('rt.expiry < :expiry')
                ->setParameter('expiry', new \DateTimeImmutable(), 'datetime_immutable')
                ->getQuery()
                ->execute();
        }

        // ODM-specific: use repository
        $repository = $this->objectManager->getRepository(RefreshToken::class);
        $expiredTokens = $repository->findBy(['expiry' => ['$lt' => new \DateTimeImmutable()]]);
        foreach ($expiredTokens as $token) {
            $this->objectManager->remove($token);
        }
        $this->objectManager->flush();

        return count($expiredTokens);
    }
}
