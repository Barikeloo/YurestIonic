<?php

namespace App\Tables\Domain\Entity;

use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Tables\Domain\ValueObject\TableName;
use App\Tables\Domain\ValueObject\ZoneId;

class Table
{
	private function __construct(
		private Uuid $id,
		private ZoneId $zoneId,
		private TableName $name,
		private DomainDateTime $createdAt,
		private DomainDateTime $updatedAt,
	) {}

	public static function dddCreate(ZoneId $zoneId, TableName $name): self
	{
		$now = DomainDateTime::now();

		return new self(
			id: Uuid::generate(),
			zoneId: $zoneId,
			name: $name,
			createdAt: $now,
			updatedAt: $now,
		);
	}

	public static function fromPersistence(
		string $id,
		string $zoneId,
		string $name,
		\DateTimeImmutable $createdAt,
		\DateTimeImmutable $updatedAt,
	): self {
		return new self(
			id: Uuid::create($id),
			zoneId: ZoneId::create($zoneId),
			name: TableName::create($name),
			createdAt: DomainDateTime::create($createdAt),
			updatedAt: DomainDateTime::create($updatedAt),
		);
	}

	public function update(ZoneId $zoneId, TableName $name): void
	{
		$this->zoneId = $zoneId;
		$this->name = $name;
		$this->touch();
	}

	public function id(): Uuid
	{
		return $this->id;
	}

	public function zoneId(): ZoneId
	{
		return $this->zoneId;
	}

	public function name(): string
	{
		return $this->name->value();
	}

	public function createdAt(): DomainDateTime
	{
		return $this->createdAt;
	}

	public function updatedAt(): DomainDateTime
	{
		return $this->updatedAt;
	}

	private function touch(): void
	{
		$this->updatedAt = DomainDateTime::now();
	}
}

