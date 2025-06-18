<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\Model;

use League\Bundle\OAuth2ServerBundle\ValueObject\Scope;

class AccessToken implements AccessTokenInterface
{
    /**
     * @var string
     */
    protected $identifier;

    /**
     * @var \DateTimeInterface
     */
    protected $expiry;

    /**
     * @var string|null
     */
    protected $userIdentifier;

    /**
     * @var ClientInterface
     */
    protected $client;

    /**
     * @var list<Scope>
     */
    protected $scopes;

    /**
     * @var bool
     */
    protected $revoked = false;

    /**
     * @param list<Scope> $scopes
     */
    public function __construct(
        string $identifier,
        \DateTimeInterface $expiry,
        ClientInterface $client,
        ?string $userIdentifier,
        array $scopes,
    ) {
        $this->identifier = $identifier;
        $this->expiry = $expiry;
        $this->client = $client;
        $this->userIdentifier = $userIdentifier;
        $this->scopes = $scopes;
    }

    public function __toString(): string
    {
        return $this->getIdentifier();
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getExpiry(): \DateTimeInterface
    {
        return $this->expiry;
    }

    public function getUserIdentifier(): ?string
    {
        return $this->userIdentifier;
    }

    public function getClient(): ClientInterface
    {
        return $this->client;
    }

    public function getScopes(): array
    {
        return $this->scopes;
    }

    public function isRevoked(): bool
    {
        return $this->revoked;
    }

    public function revoke(): AccessTokenInterface
    {
        $this->revoked = true;

        return $this;
    }
}
