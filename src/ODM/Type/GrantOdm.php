<?php

namespace League\Bundle\OAuth2ServerBundle\ODM\Type;

use Doctrine\ODM\MongoDB\Types\Type;
use LogicException;
use League\Bundle\OAuth2ServerBundle\ValueObject\Grant as GrantModel;

use function Implode;

/**
 * Class GrantOdm
 *
 * @package Trikoder\Bundle\OAuth2Bundle\ODM\Type
 */
final class GrantOdm extends Type
{

    use ImplodedArrayOdm;

    /**
     * Checks if the value can be imploded (converted to string).
     *
     * @param mixed $value
     * @throws LogicException
     */
    private function assertValueCanBeImploded($value): void
    {
        if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString'))) {
            throw new LogicException('Value cannot be imploded: must be scalar or stringable object.');
        }
    }


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
        return '$return = explode(\League\Bundle\OAuth2ServerBundle\ODM\Type\GrantOdm::VALUE_DELIMITER, $value);';
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
     * @param array $values
     *
     * @return array
     */
    protected function convertDatabaseValues(array $values): array
    {
        foreach ($values as &$value) {
            $value = new GrantModel($value);
        }

        return $values;
    }
}
