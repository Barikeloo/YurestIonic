<?php

declare(strict_types=1);

namespace App\Sale\Domain\ValueObject;

final class DocumentType
{
    private const VALID_TYPES = ['simplified', 'full_invoice', 'credit_note'];

    private function __construct(private readonly string $value)
    {
        if (! in_array($value, self::VALID_TYPES, true)) {
            throw new \InvalidArgumentException("Invalid document type: {$value}");
        }
    }

    public static function create(string $value): self
    {
        return new self($value);
    }

    public static function simplified(): self
    {
        return new self('simplified');
    }

    public static function fullInvoice(): self
    {
        return new self('full_invoice');
    }

    public static function creditNote(): self
    {
        return new self('credit_note');
    }

    public function value(): string
    {
        return $this->value;
    }

    public function isSimplified(): bool
    {
        return $this->value === 'simplified';
    }

    public function isFullInvoice(): bool
    {
        return $this->value === 'full_invoice';
    }

    public function isCreditNote(): bool
    {
        return $this->value === 'credit_note';
    }

    public function equals(DocumentType $other): bool
    {
        return $this->value === $other->value;
    }
}
