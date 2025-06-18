<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Manager\Doctrine;

use Doctrine\Persistence\ObjectManager;
use League\Bundle\OAuth2ServerBundle\Manager\ClientFilter;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\Client;

final class ClientManager implements ClientManagerInterface
{
    /**
     * @var ObjectManager
     */
    private $objectManager;

    public function __construct(ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * {@inheritdoc}
     */
    public function find(string $identifier): ?Client
    {
        return $this->objectManager->find(Client::class, $identifier);
    }

    /**
     * {@inheritdoc}
     */
    public function save(\League\Bundle\OAuth2ServerBundle\Model\ClientInterface $client): void
    {
        $this->objectManager->persist($client);
        $this->objectManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function remove(\League\Bundle\OAuth2ServerBundle\Model\ClientInterface $client): void
    {
        $this->objectManager->remove($client);
        $this->objectManager->flush();
    }

    /**
     * {@inheritdoc}
     */
    public function list(?ClientFilter $clientFilter): array
    {
        $repository = $this->objectManager->getRepository(Client::class);
        $criteria = self::filterToCriteria($clientFilter);

        return $repository->findBy($criteria);
    }

    private static function filterToCriteria(?ClientFilter $clientFilter): array
    {
        if (null === $clientFilter || false === $clientFilter->hasFilters()) {
            return [];
        }

        $criteria = [];

        $grants = $clientFilter->getGrants();
        if ($grants) {
            $criteria['grants'] = $grants;
        }

        $redirectUris = $clientFilter->getRedirectUris();
        if ($redirectUris) {
            $criteria['redirect_uris'] = $redirectUris;
        }

        $scopes = $clientFilter->getScopes();
        if ($scopes) {
            $criteria['scopes'] = $scopes;
        }

        return $criteria;
    }
}
