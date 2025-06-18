<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\Doctrine;

use Doctrine\Persistence\ObjectManager;
use Doctrine\ODM\MongoDB\DocumentManager;
use League\Bundle\OAuth2ServerBundle\Manager\AccessTokenManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AccessToken;
use League\Bundle\OAuth2ServerBundle\Model\AccessTokenInterface;

final class AccessTokenManager implements AccessTokenManagerInterface
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    /** @var bool */
    private $persistAccessToken;

    public function __construct(ObjectManager $objectManager, bool $persistAccessToken)
    {
        $this->objectManager = $objectManager;
        $this->persistAccessToken = $persistAccessToken;
    }

    public function find(string $identifier): ?AccessTokenInterface
    {
        if (!$this->persistAccessToken) {
            return null;
        }

        return $this->objectManager->find(AccessToken::class, $identifier);
    }

    public function save(AccessTokenInterface $accessToken): void
    {
        if (!$this->persistAccessToken) {
            return;
        }

        $this->objectManager->persist($accessToken);
        $this->objectManager->flush();
    }

    public function clearExpired(): int
    {
        if (!$this->persistAccessToken) {
            return 0;
        }
     /*   if ($this->objectManager instanceof DocumentManager) {
            return $this->objectManager->createQueryBuilder()
                ->remove(AccessToken::class)
                ->field('expiry')->lte(new \DateTime())
                ->getQuery()
                ->execute()
                ->getDeletedCount();
        }

        /** @var int */
      /*  return $this->objectManager->createQueryBuilder()
            ->delete(AccessToken::class, 'at')
            ->where('at.expiry < :expiry')
            ->setParameter('expiry', new \DateTimeImmutable(), 'datetime_immutable')
            ->getQuery()
            ->execute();
            */
             // ORM-specific: use DQL
        if (method_exists($this->objectManager, 'createQueryBuilder')) {
            /** @var int */
            return $this->objectManager->createQueryBuilder()
                ->delete(AccessToken::class, 'at')
                ->where('at.expiry < :expiry')
                ->setParameter('expiry', new \DateTimeImmutable(), 'datetime_immutable')
                ->getQuery()
                ->execute();
        }

        // ODM-specific: use repository
        $repository = $this->objectManager->getRepository(AccessToken::class);
        $expiredTokens = $repository->findBy(['expiry' => ['$lt' => new \DateTimeImmutable()]]);
        foreach ($expiredTokens as $token) {
            $this->objectManager->remove($token);
        }
        $this->objectManager->flush();

        return count($expiredTokens);
    }
}
