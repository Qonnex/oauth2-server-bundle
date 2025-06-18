<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\ODM\Type;

use Doctrine\ODM\MongoDB\Types\Type;
use LogicException;
use League\Bundle\OAuth2ServerBundle\ValueObject\RedirectUri as RedirectUriModel;

use function explode;
use function implode;

/**
 * Class RedirectUriOdm
 *
 * @package League\Bundle\OAuth2ServerBundle\ODM\Type
 */
final class RedirectUriOdm extends Type
{

    use ImplodedArrayOdm;

    /**
     * {@inheritdoc}
     */
    public function convertToPHPValue($value)
    {
        if (null === $value) {
            return [];
        }

        $values = explode(self::VALUE_DELIMITER, $value);

        return $this->convertDatabaseValues($values);
    }

    /**
     * {@inheritdoc}
     */
    public function closureToPHP(): string
    {
        return '$return = explode(\League\Bundle\OAuth2ServerBundle\ODM\Type\RedirectUriOdm::VALUE_DELIMITER, $value);';
    }

    /**
     * {@inheritdoc}
     */
    public function convertToDatabaseValue($value)
    {
        if (!\is_array($value)) {
            throw new LogicException('This type can only be used in combination with arrays.');
        }

        if (0 === \count($value)) {
            return null;
        }
        foreach ($value as $item) {
            $this->assertValueCanBeImploded($item);
        }

        return implode(self::VALUE_DELIMITER, $value);
    }

    /**
     * {@inheritdoc}
     */
    protected function convertDatabaseValues(array $values): array
    {
        foreach ($values as &$value) {
            $value = new RedirectUriModel($value);
        }

        return $values;
    }
}
