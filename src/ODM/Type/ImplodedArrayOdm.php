<?php

declare(strict_types=1);

namespace League\Bundle\OAuth2ServerBundle\ODM\Type;


/**
 * @template T
 */
trait ImplodedArrayOdm
{
    public const VALUE_DELIMITER = ' ';

    public function convertToDatabaseValue($value)
    {
        if (!\is_array($value)) {
            throw new \LogicException('This type can only be used in combination with arrays.');
        }

        if (0 === \count($value)) {
            return null;
        }

        foreach ($value as $item) {
            $this->assertValueCanBeImploded($item);
        }

        return implode(static::VALUE_DELIMITER, $value);
    }

    public function convertToPHPValue($value)
    {
        if (null === $value) {
            return [];
        }

        $values = explode(static::VALUE_DELIMITER, $value);

        return $this->convertDatabaseValues($values);
    }

    private function assertValueCanBeImploded($value): void
    {
        if (null === $value) {
            return;
        }

        if (\is_scalar($value)) {
            return;
        }

        if (\is_object($value) && method_exists($value, '__toString')) {
            return;
        }

        throw new \InvalidArgumentException(\sprintf('The value of \'%s\' type cannot be imploded.', \gettype($value)));
    }

    abstract protected function convertDatabaseValues(array $values): array;
}
