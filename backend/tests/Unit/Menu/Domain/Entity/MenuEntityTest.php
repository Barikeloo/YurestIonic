<?php

namespace Tests\Unit\Menu\Domain\Entity;

use App\Menu\Domain\Entity\Menu;
use App\Menu\Domain\Entity\MenuItem;
use App\Menu\Domain\Entity\MenuSection;
use App\Menu\Domain\Exception\MenuArchivedException;
use App\Menu\Domain\Exception\MenuInvalidConfigurationException;
use App\Menu\Domain\ValueObject\MenuItemExtraPrice;
use App\Menu\Domain\ValueObject\MenuAvailability;
use App\Menu\Domain\ValueObject\MenuDescription;
use App\Menu\Domain\ValueObject\MenuName;
use App\Menu\Domain\ValueObject\MenuPrice;
use App\Menu\Domain\ValueObject\MenuSectionChoiceRule;
use App\Menu\Domain\ValueObject\MenuSectionName;
use App\Menu\Domain\ValueObject\MenuValidity;
use App\Shared\Domain\ValueObject\DomainDateTime;
use App\Shared\Domain\ValueObject\Uuid;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

class MenuEntityTest extends TestCase
{
    use MenuEntityTestHelper;

    public function test_ddd_create_builds_menu(): void
    {
        $menu = $this->createValidMenu();

        $this->assertInstanceOf(Uuid::class, $menu->id());
        $this->assertInstanceOf(Uuid::class, $menu->taxId());
        $this->assertSame('Menú del día', $menu->name()->value());
        $this->assertSame('Descripción', $menu->description()->value());
        $this->assertSame(1500, $menu->price()->value());
        $this->assertTrue($menu->isActive());
        $this->assertFalse($menu->isArchived());
        $this->assertCount(1, $menu->sections());
        $this->assertInstanceOf(DomainDateTime::class, $menu->createdAt());
        $this->assertInstanceOf(DomainDateTime::class, $menu->updatedAt());
        $this->assertNull($menu->archivedAt());
    }

    public function test_ddd_create_without_sections_throws_exception(): void
    {
        $this->expectException(MenuInvalidConfigurationException::class);
        $this->expectExceptionMessage('A menu must contain at least one section.');

        Menu::dddCreate(
            taxId: Uuid::generate(),
            name: MenuName::create('Test'),
            description: MenuDescription::empty(),
            price: MenuPrice::create(0),
            validity: MenuValidity::always(),
            availability: MenuAvailability::alwaysAvailable(),
            active: true,
            sections: [],
        );
    }

    public function test_update_header(): void
    {
        $menu = $this->createValidMenu();
        $previousUpdatedAt = $menu->updatedAt()->value();

        sleep(1);

        $menu->updateHeader(
            taxId: Uuid::generate(),
            name: MenuName::create('Menú noche'),
            description: MenuDescription::empty(),
            price: MenuPrice::create(2000),
            validity: MenuValidity::always(),
            availability: MenuAvailability::alwaysAvailable(),
            active: false,
        );

        $this->assertSame('Menú noche', $menu->name()->value());
        $this->assertTrue($menu->description()->isEmpty());
        $this->assertSame(2000, $menu->price()->value());
        $this->assertFalse($menu->isActive());
        $this->assertGreaterThan($previousUpdatedAt, $menu->updatedAt()->value());
    }

    public function test_update_header_on_archived_menu_throws_exception(): void
    {
        $menu = $this->createValidMenu();
        $menu->archive();

        $this->expectException(MenuArchivedException::class);
        $this->expectExceptionMessage('Cannot modify archived menu');

        $menu->updateHeader(
            taxId: Uuid::generate(),
            name: MenuName::create('Nuevo'),
            description: MenuDescription::empty(),
            price: MenuPrice::create(1000),
            validity: MenuValidity::always(),
            availability: MenuAvailability::alwaysAvailable(),
            active: true,
        );
    }

    public function test_replace_sections(): void
    {
        $menu = $this->createValidMenu();
        $previousUpdatedAt = $menu->updatedAt()->value();

        $newSection = $this->createSection('Postres');
        $menu->replaceSections([$newSection]);

        sleep(1);

        $this->assertCount(1, $menu->sections());
        $this->assertSame('Postres', $menu->sections()[0]->name()->value());
        $this->assertGreaterThan($previousUpdatedAt, $menu->updatedAt()->value());
    }

    public function test_replace_sections_on_archived_menu_throws_exception(): void
    {
        $menu = $this->createValidMenu();
        $menu->archive();

        $this->expectException(MenuArchivedException::class);

        $menu->replaceSections([$this->createSection('Nueva')]);
    }

    public function test_replace_sections_with_empty_throws_exception(): void
    {
        $menu = $this->createValidMenu();

        $this->expectException(MenuInvalidConfigurationException::class);
        $this->expectExceptionMessage('A menu must contain at least one section.');

        $menu->replaceSections([]);
    }

    public function test_activate(): void
    {
        $menu = $this->createValidMenu(active: false);
        $previousUpdatedAt = $menu->updatedAt()->value();

        sleep(1);
        $menu->activate();

        $this->assertTrue($menu->isActive());
        $this->assertGreaterThan($previousUpdatedAt, $menu->updatedAt()->value());
    }

    public function test_activate_when_already_active_is_noop(): void
    {
        $menu = $this->createValidMenu(active: true);
        $updatedAt = $menu->updatedAt()->value();

        $menu->activate();

        $this->assertTrue($menu->isActive());
        $this->assertEquals($updatedAt, $menu->updatedAt()->value());
    }

    public function test_activate_on_archived_menu_throws_exception(): void
    {
        $menu = $this->createValidMenu();
        $menu->archive();

        $this->expectException(MenuArchivedException::class);

        $menu->activate();
    }

    public function test_deactivate(): void
    {
        $menu = $this->createValidMenu(active: true);
        $previousUpdatedAt = $menu->updatedAt()->value();

        sleep(1);
        $menu->deactivate();

        $this->assertFalse($menu->isActive());
        $this->assertGreaterThan($previousUpdatedAt, $menu->updatedAt()->value());
    }

    public function test_deactivate_when_already_inactive_is_noop(): void
    {
        $menu = $this->createValidMenu(active: false);
        $updatedAt = $menu->updatedAt()->value();

        $menu->deactivate();

        $this->assertFalse($menu->isActive());
        $this->assertEquals($updatedAt, $menu->updatedAt()->value());
    }

    public function test_deactivate_on_archived_menu_throws_exception(): void
    {
        $menu = $this->createValidMenu();
        $menu->archive();

        $this->expectException(MenuArchivedException::class);

        $menu->deactivate();
    }

    public function test_archive(): void
    {
        $menu = $this->createValidMenu(active: true);
        $previousUpdatedAt = $menu->updatedAt()->value();

        sleep(1);
        $menu->archive();

        $this->assertTrue($menu->isArchived());
        $this->assertFalse($menu->isActive());
        $this->assertInstanceOf(DomainDateTime::class, $menu->archivedAt());
        $this->assertGreaterThan($previousUpdatedAt, $menu->updatedAt()->value());
    }

    public function test_archive_when_already_archived_is_noop(): void
    {
        $menu = $this->createValidMenu();
        $menu->archive();
        $archivedAt = $menu->archivedAt();

        $menu->archive();

        $this->assertSame($archivedAt->value()->format('U'), $menu->archivedAt()->value()->format('U'));
    }

    public function test_is_available_at_returns_true_when_active_and_valid(): void
    {
        $menu = $this->createValidMenu(active: true);
        // always available

        $this->assertTrue($menu->isAvailableAt(new DateTimeImmutable('2026-06-15 12:00:00')));
    }

    public function test_is_available_at_returns_false_when_inactive(): void
    {
        $menu = $this->createValidMenu(active: false);

        $this->assertFalse($menu->isAvailableAt(new DateTimeImmutable('2026-06-15 12:00:00')));
    }

    public function test_is_available_at_returns_false_when_archived(): void
    {
        $menu = $this->createValidMenu(active: true);
        $menu->archive();

        $this->assertFalse($menu->isAvailableAt(new DateTimeImmutable('2026-06-15 12:00:00')));
    }

    public function test_is_available_at_with_validity_range(): void
    {
        $menu = Menu::dddCreate(
            taxId: Uuid::generate(),
            name: MenuName::create('Temporada'),
            description: MenuDescription::empty(),
            price: MenuPrice::create(1000),
            validity: MenuValidity::create(
                new DateTimeImmutable('2026-06-01'),
                new DateTimeImmutable('2026-06-30'),
            ),
            availability: MenuAvailability::alwaysAvailable(),
            active: true,
            sections: [$this->createSection('Única')],
        );

        $this->assertTrue($menu->isAvailableAt(new DateTimeImmutable('2026-06-15')));
        $this->assertFalse($menu->isAvailableAt(new DateTimeImmutable('2026-05-31')));
        $this->assertFalse($menu->isAvailableAt(new DateTimeImmutable('2026-07-01')));
    }

    public function test_from_persistence_rebuilds_menu(): void
    {
        $id = Uuid::generate()->value();
        $taxId = Uuid::generate()->value();
        $now = new DateTimeImmutable;
        $item = MenuItem::dddCreate(
            sectionId: Uuid::generate(),
            productId: Uuid::generate(),
            variantId: null,
            extraPrice: MenuItemExtraPrice::zero(),
            position: 0,
        );
        $section = MenuSection::dddCreate(
            menuId: Uuid::create($id),
            name: MenuSectionName::create('Principal'),
            position: 0,
            choiceRule: MenuSectionChoiceRule::chooseOne(),
            items: [$item],
        );

        $menu = Menu::fromPersistence(
            id: $id,
            taxId: $taxId,
            name: 'Menú test',
            description: 'Desc',
            price: 2000,
            active: true,
            validityFrom: new DateTimeImmutable('2026-01-01'),
            validityTo: null,
            availableDays: 127,
            availableFromTime: null,
            availableToTime: null,
            sections: [$section],
            createdAt: $now,
            updatedAt: $now,
            archivedAt: null,
        );

        $this->assertSame($id, $menu->id()->value());
        $this->assertSame($taxId, $menu->taxId()->value());
        $this->assertSame('Menú test', $menu->name()->value());
        $this->assertSame('Desc', $menu->description()->value());
        $this->assertSame(2000, $menu->price()->value());
        $this->assertTrue($menu->isActive());
        $this->assertFalse($menu->isArchived());
        $this->assertNull($menu->archivedAt());
        $this->assertCount(1, $menu->sections());
        $this->assertEquals($now, $menu->createdAt()->value());
        $this->assertEquals($now, $menu->updatedAt()->value());
    }

    public function test_from_persistence_with_archived(): void
    {
        $now = new DateTimeImmutable;

        $menu = Menu::fromPersistence(
            id: Uuid::generate()->value(),
            taxId: Uuid::generate()->value(),
            name: 'Archivado',
            description: null,
            price: 1000,
            active: false,
            validityFrom: null,
            validityTo: null,
            availableDays: 127,
            availableFromTime: null,
            availableToTime: null,
            sections: [$this->createSection('Única')],
            createdAt: $now,
            updatedAt: $now,
            archivedAt: $now,
        );

        $this->assertTrue($menu->isArchived());
        $this->assertFalse($menu->isActive());
        $this->assertEquals($now, $menu->archivedAt()?->value());
    }

    private function createValidMenu(bool $active = true): Menu
    {
        return Menu::dddCreate(
            taxId: Uuid::generate(),
            name: MenuName::create('Menú del día'),
            description: MenuDescription::create('Descripción'),
            price: MenuPrice::create(1500),
            validity: MenuValidity::always(),
            availability: MenuAvailability::alwaysAvailable(),
            active: $active,
            sections: [$this->createSection('Primero')],
        );
    }
}
