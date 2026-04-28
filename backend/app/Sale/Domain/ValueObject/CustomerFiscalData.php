<?php

declare(strict_types=1);

namespace App\Sale\Domain\ValueObject;

final class CustomerFiscalData
{
    private const TAX_ID_MAX_LENGTH = 64;

    private const LEGAL_NAME_MAX_LENGTH = 255;

    private const ADDRESS_MAX_LENGTH = 500;

    private readonly string $taxId;

    private readonly string $legalName;

    private readonly ?string $address;

    private function __construct(string $taxId, string $legalName, ?string $address)
    {
        $taxId = strtoupper(trim($taxId));
        $legalName = trim($legalName);
        $address = $address !== null ? trim($address) : null;

        if ($taxId === '') {
            throw new \InvalidArgumentException('Customer tax id cannot be empty.');
        }

        if (mb_strlen($taxId) > self::TAX_ID_MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Customer tax id cannot exceed %d characters.', self::TAX_ID_MAX_LENGTH),
            );
        }

        if ($legalName === '') {
            throw new \InvalidArgumentException('Customer legal name cannot be empty.');
        }

        if (mb_strlen($legalName) > self::LEGAL_NAME_MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Customer legal name cannot exceed %d characters.', self::LEGAL_NAME_MAX_LENGTH),
            );
        }

        if ($address === '') {
            $address = null;
        }

        if ($address !== null && mb_strlen($address) > self::ADDRESS_MAX_LENGTH) {
            throw new \InvalidArgumentException(
                sprintf('Customer address cannot exceed %d characters.', self::ADDRESS_MAX_LENGTH),
            );
        }

        $this->taxId = $taxId;
        $this->legalName = $legalName;
        $this->address = $address;
    }

    public static function create(string $taxId, string $legalName, ?string $address = null): self
    {
        return new self($taxId, $legalName, $address);
    }

    public static function fromArray(array $data): self
    {
        $taxId = $data['tax_id'] ?? null;
        $legalName = $data['legal_name'] ?? null;
        $address = $data['address'] ?? null;

        if (! is_string($taxId)) {
            throw new \InvalidArgumentException('Customer fiscal data: tax_id is required.');
        }
        if (! is_string($legalName)) {
            throw new \InvalidArgumentException('Customer fiscal data: legal_name is required.');
        }
        if ($address !== null && ! is_string($address)) {
            throw new \InvalidArgumentException('Customer fiscal data: address must be a string.');
        }

        return new self($taxId, $legalName, $address);
    }

    public function taxId(): string
    {
        return $this->taxId;
    }

    public function legalName(): string
    {
        return $this->legalName;
    }

    public function address(): ?string
    {
        return $this->address;
    }

    public function toArray(): array
    {
        return [
            'tax_id' => $this->taxId,
            'legal_name' => $this->legalName,
            'address' => $this->address,
        ];
    }
}
