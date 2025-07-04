<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Repository;

use League\Bundle\OAuth2ServerBundle\Converter\ScopeConverterInterface;
use League\Bundle\OAuth2ServerBundle\Event\ScopeResolveEvent;
use League\Bundle\OAuth2ServerBundle\Manager\ClientManagerInterface;
use League\Bundle\OAuth2ServerBundle\Manager\ScopeManagerInterface;
use League\Bundle\OAuth2ServerBundle\Model\AbstractClient;
use League\Bundle\OAuth2ServerBundle\OAuth2Events;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant;
use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\ScopeEntityInterface;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class ScopeRepository implements ScopeRepositoryInterface
{
    /**
     * @var ScopeManagerInterface
     */
    private $scopeManager;

    /**
     * @var ClientManagerInterface
     */
    private $clientManager;

    /**
     * @var ScopeConverterInterface
     */
    private $scopeConverter;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    public function __construct(
        ScopeManagerInterface $scopeManager,
        ClientManagerInterface $clientManager,
        ScopeConverterInterface $scopeConverter,
        EventDispatcherInterface $eventDispatcher,
    ) {
        $this->scopeManager = $scopeManager;
        $this->clientManager = $clientManager;
        $this->scopeConverter = $scopeConverter;
        $this->eventDispatcher = $eventDispatcher;
    }

    public function getScopeEntityByIdentifier($identifier): ?ScopeEntityInterface
    {
        $scope = $this->scopeManager->find($identifier);

        if (null === $scope) {
            return null;
        }

        return $this->scopeConverter->toLeague($scope);
    }

    /**
     * @param ScopeEntityInterface[] $scopes
     * @param non-empty-string $grantType
     *
     * @return list<ScopeEntityInterface>
     */
    public function finalizeScopes(
        array $scopes,
        string $grantType,
        ClientEntityInterface $clientEntity,
        string|int|null $userIdentifier = null,
        ?string $authCodeId = null,
    ): array {
        /** @var AbstractClient $client */
        $client = $this->clientManager->find($clientEntity->getIdentifier());

        $scopes = $this->setupScopes($client, $this->scopeConverter->toDomainArray(array_values($scopes)));
        $scopes = array_map(fn($scope) => $scope instanceof Scope ? $scope : new Scope($scope), $scopes);
        $event = $this->eventDispatcher->dispatch(
            new ScopeResolveEvent(
                $scopes,
                new Grant($grantType),
                $client,
                $userIdentifier
            ),
            OAuth2Events::SCOPE_RESOLVE
        );

        return $this->scopeConverter->toLeagueArray($event->getScopes());
    }

    /**
     * @param list<Scope> $requestedScopes
     *
     * @return list<Scope>
     */
    private function setupScopes(AbstractClient $client, array $requestedScopes): array
    {
        $clientScopes = $client->getScopes();

        if (empty($clientScopes)) {
            return $requestedScopes;
        }

        if (empty($requestedScopes)) {
            return $clientScopes;
        }

        $finalizedScopes = [];
        $clientScopesAsStrings = array_map('strval', $clientScopes);

        foreach ($requestedScopes as $requestedScope) {
            $requestedScopeAsString = (string) $requestedScope;
            if (!\in_array($requestedScopeAsString, $clientScopesAsStrings, true)) {
                throw OAuthServerException::invalidScope($requestedScopeAsString);
            }

            $finalizedScopes[] = $requestedScope;
        }

        return $finalizedScopes;
    }
}
