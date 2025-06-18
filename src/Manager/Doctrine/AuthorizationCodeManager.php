<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\Doctrine;

use Doctrine\Persistence\ObjectManager;
use League\Bundle\OAuth2ServerBundle\Manager\AuthorizationCodeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCode;
use League\Bundle\OAuth2ServerBundle\Model\AuthorizationCodeInterface;

final class AuthorizationCodeManager implements AuthorizationCodeManagerInterface
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    public function find(string $identifier): ?AuthorizationCodeInterface
    {
        return $this->objectManager->find(AuthorizationCode::class, $identifier);
    }

    public function save(AuthorizationCodeInterface $authCode): void
    {
        $this->objectManager->persist($authCode);
        $this->objectManager->flush();
    }

    public function clearExpired(): int
    {
        // ORM-specific: use DQL
        if (method_exists($this->objectManager, 'createQueryBuilder')) {
            /** @var int */
            return $this->objectManager->createQueryBuilder()
                ->delete(AuthorizationCode::class, 'ac')
                ->where('ac.expiry < :expiry')
                ->setParameter('expiry', new \DateTimeImmutable(), 'datetime_immutable')
                ->getQuery()
                ->execute();
        }

        // ODM-specific: use repository
        $repository = $this->objectManager->getRepository(AuthorizationCode::class);
        $expiredCodes = $repository->findBy(['expiry' => ['$lt' => new \DateTimeImmutable()]]);
        foreach ($expiredCodes as $code) {
            $this->objectManager->remove($code);
        }
        $this->objectManager->flush();

        return count($expiredCodes);
    }
}
