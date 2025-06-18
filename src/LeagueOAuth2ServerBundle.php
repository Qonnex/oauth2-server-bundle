<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DoctrineOrmMappingsPass;
use Doctrine\Bundle\MongoDBBundle\DependencyInjection\Compiler\DoctrineMongoDBMappingsPass;
use League\Bundle\OAuth2ServerBundle\DependencyInjection\CompilerPass\EncryptionKeyPass;
use League\Bundle\OAuth2ServerBundle\DependencyInjection\LeagueOAuth2ServerExtension;
use League\Bundle\OAuth2ServerBundle\DependencyInjection\Security\OAuth2Factory;
use League\Bundle\OAuth2ServerBundle\Persistence\Mapping\Driver;
use Symfony\Bundle\SecurityBundle\DependencyInjection\SecurityExtension;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\ExtensionInterface;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Doctrine\ODM\MongoDB\Types\Type;
use League\Bundle\OAuth2ServerBundle\ODM\Type\GrantOdm;
use League\Bundle\OAuth2ServerBundle\ODM\Type\RedirectUriOdm;
use League\Bundle\OAuth2ServerBundle\ODM\Type\ScopeOdm;

final class LeagueOAuth2ServerBundle extends Bundle
{
    public function __construct()
    {
        if (class_exists(DoctrineMongoDBMappingsPass::class)) {
            Type::registerType('oauth2_grant', GrantOdm::class);
            Type::registerType('oauth2_redirect_uri', RedirectUriOdm::class);
            Type::registerType('oauth2_scope', ScopeOdm::class);
        }
    }
    /**
     * @return void
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        if (class_exists(DoctrineMongoDBMappingsPass::class)) {
            $this->configureDoctrineMongoExtension($container);
        } else {
            $this->configureDoctrineExtension($container);
        }

        $this->configureSecurityExtension($container);
    }

    public function getContainerExtension(): ExtensionInterface
    {
        return new LeagueOAuth2ServerExtension();
    }

    private function configureSecurityExtension(ContainerBuilder $container): void
    {
        /** @var SecurityExtension $extension */
        $extension = $container->getExtension('security');

        $extension->addAuthenticatorFactory(new OAuth2Factory());
    }

    private function configureDoctrineExtension(ContainerBuilder $container): void
    {
        $container->addCompilerPass(
            new DoctrineOrmMappingsPass(
                new Reference(Driver::class),
                ['League\Bundle\OAuth2ServerBundle\Model'],
                ['league.oauth2_server.persistence.doctrine.manager'],
                'league.oauth2_server.persistence.doctrine.enabled'
            )
        );

        $container->addCompilerPass(new EncryptionKeyPass());
    }

    private function configureDoctrineMongoExtension(ContainerBuilder $container): void
    {

        $container->addCompilerPass(
            DoctrineMongoDBMappingsPass::createXmlMappingDriver(
                [realpath(__DIR__ . '/Resources/config/doctrine/model') => 'League\Bundle\OAuth2ServerBundle\Model'],
                ['league.oauth2_server.persistence.doctrine.manager'],
                'league.oauth2_server.persistence.doctrine.enabled'
            )
        );

        $container->addCompilerPass(new EncryptionKeyPass());
    }

    public function getPath(): string
    {
        $reflected = new \ReflectionObject($this);

        return \dirname($reflected->getFileName() ?: __FILE__, 2);
    }
}
