<?php

declare(strict_types=1);

namespace App\Menu\Domain\Entity;

use App\Menu\Domain\Event\MenuActivated;
use App\Menu\Domain\Event\MenuArchived;
use App\Menu\Domain\Event\MenuCreated;
use App\Menu\Domain\Event\MenuDeactivated;
use App\Menu\Domain\Exception\MenuArchivedException;
use App\Menu\Domain\Exception\MenuInvalidConfigurationException;
use App\Menu\Domain\ValueObject\MenuAvailability;
use App\Menu\Domain\ValueObject\MenuDescription;
use App\Menu\Domain\ValueObject\MenuName;
use App\Menu\Domain\ValueObject\MenuPrice;
use App\Menu\Domain\ValueObject\MenuValidity;
use App\Shared\Domain\Event\RecordsEvents;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;

class Menu
{
    use RecordsEvents;

    private function __construct(
        private Uuid $id,
        private Uuid $taxId,
        private MenuName $name,
        private MenuDescription $description,
        private MenuPrice $price,
        private MenuValidity $validity,
        private MenuAvailability $availability,
        private bool $active,
        private array $sections,
        private DomainDateTime $createdAt,
        private DomainDateTime $updatedAt,
        private ?DomainDateTime $archivedAt = null,
    ) {}

    public static function dddCreate(
        Uuid $taxId,
        MenuName $name,
        MenuDescription $description,
        MenuPrice $price,
        MenuValidity $validity,
        MenuAvailability $availability,
        bool $active,
        array $sections,
    ): self {
        if ($sections === []) {
            throw MenuInvalidConfigurationException::emptyMenu();
        }

        $now = DomainDateTime::now();
        $id = Uuid::generate();

        $menu = new self(
            id: $id,
            taxId: $taxId,
            name: $name,
            description: $description,
            price: $price,
            validity: $validity,
            availability: $availability,
            active: $active,
            sections: $sections,
            createdAt: $now,
            updatedAt: $now,
        );

        $menu->recordEvent(new MenuCreated(
            menuUuid: $id->value(),
            menuName: $name->value(),
            active: $active,
            sectionsCount: count($sections),
        ));

        return $menu;
    }

    public static function fromPersistence(
        string $id,
        string $taxId,
        string $name,
        ?string $description,
        int $price,
        bool $active,
        ?\DateTimeImmutable $validityFrom,
        ?\DateTimeImmutable $validityTo,
        int $availableDays,
        ?string $availableFromTime,
        ?string $availableToTime,
        array $sections,
        \DateTimeImmutable $createdAt,
        \DateTimeImmutable $updatedAt,
        ?\DateTimeImmutable $archivedAt,
    ): self {
        return new self(
            id: Uuid::create($id),
            taxId: Uuid::create($taxId),
            name: MenuName::create($name),
            description: MenuDescription::create($description),
            price: MenuPrice::create($price),
            validity: MenuValidity::create($validityFrom, $validityTo),
            availability: MenuAvailability::create($availableDays, $availableFromTime, $availableToTime),
            active: $active,
            sections: $sections,
            createdAt: DomainDateTime::create($createdAt),
            updatedAt: DomainDateTime::create($updatedAt),
            archivedAt: $archivedAt !== null ? DomainDateTime::create($archivedAt) : null,
        );
    }

    public function updateHeader(
        Uuid $taxId,
        MenuName $name,
        MenuDescription $description,
        MenuPrice $price,
        MenuValidity $validity,
        MenuAvailability $availability,
        bool $active,
    ): void {
        $this->ensureNotArchived();
        $this->taxId = $taxId;
        $this->name = $name;
        $this->description = $description;
        $this->price = $price;
        $this->validity = $validity;
        $this->availability = $availability;
        $this->active = $active;
        $this->touch();
    }

    public function replaceSections(array $sections): void
    {
        $this->ensureNotArchived();
        if ($sections === []) {
            throw MenuInvalidConfigurationException::emptyMenu();
        }
        $this->sections = $sections;
        $this->touch();
    }

    public function activate(): void
    {
        $this->ensureNotArchived();
        if (! $this->active) {
            $this->active = true;
            $this->touch();

            $this->recordEvent(new MenuActivated(
                menuUuid: $this->id->value(),
                menuName: $this->name->value(),
            ));
        }
    }

    public function deactivate(): void
    {
        $this->ensureNotArchived();
        if ($this->active) {
            $this->active = false;
            $this->touch();

            $this->recordEvent(new MenuDeactivated(
                menuUuid: $this->id->value(),
                menuName: $this->name->value(),
            ));
        }
    }

    public function archive(): void
    {
        if ($this->archivedAt !== null) {
            return;
        }
        $this->active = false;
        $this->archivedAt = DomainDateTime::now();
        $this->touch();

        $this->recordEvent(new MenuArchived(
            menuUuid: $this->id->value(),
            menuName: $this->name->value(),
        ));
    }

    public function isArchived(): bool
    {
        return $this->archivedAt !== null;
    }

    public function isAvailableAt(\DateTimeImmutable $instant): bool
    {
        if (! $this->active || $this->isArchived()) {
            return false;
        }
        if (! $this->validity->isValidOnDate($instant)) {
            return false;
        }

        return $this->availability->isAvailableAt($instant);
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function taxId(): Uuid
    {
        return $this->taxId;
    }

    public function name(): MenuName
    {
        return $this->name;
    }

    public function description(): MenuDescription
    {
        return $this->description;
    }

    public function price(): MenuPrice
    {
        return $this->price;
    }

    public function validity(): MenuValidity
    {
        return $this->validity;
    }

    public function availability(): MenuAvailability
    {
        return $this->availability;
    }

    public function isActive(): bool
    {
        return $this->active;
    }

    public function sections(): array
    {
        return $this->sections;
    }

    public function createdAt(): DomainDateTime
    {
        return $this->createdAt;
    }

    public function updatedAt(): DomainDateTime
    {
        return $this->updatedAt;
    }

    public function archivedAt(): ?DomainDateTime
    {
        return $this->archivedAt;
    }

    public function snapshot(): array
    {
        return [
            'name' => $this->name->value(),
            'price' => $this->price->value(),
            'active' => $this->active,
            'tax_id' => $this->taxId->value(),
        ];
    }

    private function ensureNotArchived(): void
    {
        if ($this->isArchived()) {
            throw MenuArchivedException::cannotModify($this->id->value());
        }
    }

    private function touch(): void
    {
        $this->updatedAt = DomainDateTime::now();
    }
}
