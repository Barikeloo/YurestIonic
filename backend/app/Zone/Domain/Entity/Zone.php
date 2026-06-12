<?php

namespace App\Zone\Domain\Entity;

use App\Shared\Domain\Event\RecordsEvents;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use App\Zone\Domain\Event\ZoneCreated;
use App\Zone\Domain\Event\ZoneDeleted;
use App\Zone\Domain\Event\ZoneUpdated;
use App\Zone\Domain\ValueObject\ZoneName;

class Zone
{
    use RecordsEvents;

    private function __construct(
        private Uuid $id,
        private ZoneName $name,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
    ) {}

    public static function dddCreate(ZoneName $name): self
    {
        $now = DomainDateTime::now();

        $zone = new self(
            id: Uuid::generate(),
            name: $name,
            createdAt: $now,
            updatedAt: $now,
        );

        $zone->recordEvent(new ZoneCreated(
            zoneId: $zone->id->value(),
            name: $zone->name->value(),
        ));

        return $zone;
    }

    public static function fromPersistence(
        string $id,
        string $name,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
    ): self {
        return new self(
            id: Uuid::create($id),
            name: ZoneName::create($name),
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
        );
    }

    public function rename(ZoneName $name): void
    {
        $before = ['name' => $this->name->value()];

        $this->name = $name;
        $this->touch();

        $this->recordEvent(new ZoneUpdated(
            zoneId: $this->id->value(),
            before: $before,
            after: ['name' => $this->name->value()],
        ));
    }

    public function delete(): void
    {
        $this->recordEvent(new ZoneDeleted(
            zoneId: $this->id->value(),
            name: $this->name->value(),
        ));
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function name(): ZoneName
    {
        return $this->name;
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
