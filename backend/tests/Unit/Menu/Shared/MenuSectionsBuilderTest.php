<?php

namespace Tests\Unit\Menu\Shared;

use App\Menu\Application\Shared\MenuItemInput;
use App\Menu\Application\Shared\MenuSectionInput;
use App\Menu\Application\Shared\MenuSectionsBuilder;
use App\Menu\Domain\Entity\MenuItem;
use App\Menu\Domain\Entity\MenuSection;
use App\Menu\Domain\Exception\MenuInvalidConfigurationException;
use App\Shared\Domain\ValueObject\Uuid;
use PHPUnit\Framework\TestCase;

class MenuSectionsBuilderTest extends TestCase
{
    public function test_build_creates_sections_with_items(): void
    {
        $menuId = Uuid::generate();
        $sectionInputs = [
            new MenuSectionInput(
                name: 'Primero',
                position: 0,
                minChoices: 1,
                maxChoices: 1,
                items: [
                    new MenuItemInput(
                        productId: 'a0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11',
                        variantId: null,
                        extraPrice: 0,
                        position: 0,
                    ),
                    new MenuItemInput(
                        productId: 'b0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11',
                        variantId: 'c0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11',
                        extraPrice: 200,
                        position: 1,
                    ),
                ],
            ),
            new MenuSectionInput(
                name: 'Segundo',
                position: 1,
                minChoices: 0,
                maxChoices: 1,
                items: [
                    new MenuItemInput(
                        productId: 'd0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11',
                        variantId: null,
                        extraPrice: 0,
                        position: 0,
                    ),
                ],
            ),
        ];

        $sections = MenuSectionsBuilder::build($menuId, $sectionInputs);

        $this->assertCount(2, $sections);
        $this->assertInstanceOf(MenuSection::class, $sections[0]);
        $this->assertInstanceOf(MenuSection::class, $sections[1]);

        $this->assertSame('Primero', $sections[0]->name()->value());
        $this->assertSame(0, $sections[0]->position());
        $this->assertSame(1, $sections[0]->choiceRule()->min());
        $this->assertSame(1, $sections[0]->choiceRule()->max());

        $this->assertCount(2, $sections[0]->items());
        $this->assertInstanceOf(MenuItem::class, $sections[0]->items()[0]);
        $this->assertInstanceOf(MenuItem::class, $sections[0]->items()[1]);
        $this->assertSame('a0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11', $sections[0]->items()[0]->productId()->value());
        $this->assertNull($sections[0]->items()[0]->variantId());
        $this->assertTrue($sections[0]->items()[0]->extraPrice()->isZero());
        $this->assertSame(200, $sections[0]->items()[1]->extraPrice()->value());
        $this->assertSame('c0eebc99-9c0b-4ef8-bb6d-6bb9bd380b11', $sections[0]->items()[1]->variantId()?->value());

        $this->assertSame('Segundo', $sections[1]->name()->value());
        $this->assertSame(1, $sections[1]->position());
        $this->assertSame(0, $sections[1]->choiceRule()->min());
        $this->assertSame(1, $sections[1]->choiceRule()->max());
        $this->assertCount(1, $sections[1]->items());
    }

    public function test_build_with_empty_inputs_throws_exception(): void
    {
        $this->expectException(MenuInvalidConfigurationException::class);
        $this->expectExceptionMessage('A menu must contain at least one section.');

        MenuSectionsBuilder::build(Uuid::generate(), []);
    }

    public function test_build_with_section_having_no_items_throws_exception(): void
    {
        $this->expectException(MenuInvalidConfigurationException::class);
        $this->expectExceptionMessage("Menu section 'Vacía' must contain at least one item.");

        MenuSectionsBuilder::build(Uuid::generate(), [
            new MenuSectionInput('Vacía', 0, 1, 1, []),
        ]);
    }
}
