<?php

declare(strict_types=1);

namespace Tests\Unit\Tables\Domain;

use App\Tables\Domain\ValueObject\TableLayout;
use PHPUnit\Framework\TestCase;

class TableLayoutTest extends TestCase
{
    public function test_creates_valid_rect_layout(): void
    {
        $layout = TableLayout::create(100, 50, 120, 70, 'rect');

        $this->assertSame(100,    $layout->posX);
        $this->assertSame(50,     $layout->posY);
        $this->assertSame(120,    $layout->width);
        $this->assertSame(70,     $layout->height);
        $this->assertSame('rect', $layout->shape);
    }

    public function test_creates_valid_circle_layout(): void
    {
        $layout = TableLayout::create(0, 0, 80, 80, 'circle');

        $this->assertSame('circle', $layout->shape);
    }

    public function test_to_array_returns_all_fields(): void
    {
        $layout = TableLayout::create(200, 150, 100, 60, 'rect');

        $this->assertSame([
            'pos_x'  => 200,
            'pos_y'  => 150,
            'width'  => 100,
            'height' => 60,
            'shape'  => 'rect',
        ], $layout->toArray());
    }

    public function test_pos_x_cannot_be_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/pos_x/');

        TableLayout::create(-1, 0, 100, 60, 'rect');
    }

    public function test_pos_x_cannot_exceed_canvas_width(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/pos_x/');

        TableLayout::create(1201, 0, 100, 60, 'rect');
    }

    public function test_pos_y_cannot_be_negative(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/pos_y/');

        TableLayout::create(0, -1, 100, 60, 'rect');
    }

    public function test_pos_y_cannot_exceed_canvas_height(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/pos_y/');

        TableLayout::create(0, 801, 100, 60, 'rect');
    }

    public function test_width_cannot_be_below_minimum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/width/');

        TableLayout::create(0, 0, 19, 60, 'rect');
    }

    public function test_width_cannot_exceed_maximum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/width/');

        TableLayout::create(0, 0, 601, 60, 'rect');
    }

    public function test_height_cannot_be_below_minimum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/height/');

        TableLayout::create(0, 0, 100, 19, 'rect');
    }

    public function test_height_cannot_exceed_maximum(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/height/');

        TableLayout::create(0, 0, 100, 601, 'rect');
    }

    public function test_invalid_shape_throws_exception(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/shape/');

        TableLayout::create(0, 0, 100, 60, 'triangle');
    }

    public function test_boundary_values_are_accepted(): void
    {
        // min/max of canvas and size
        $a = TableLayout::create(0, 0, 20, 20, 'rect');
        $b = TableLayout::create(1200, 800, 600, 600, 'circle');

        $this->assertSame(0,    $a->posX);
        $this->assertSame(1200, $b->posX);
        $this->assertSame(800,  $b->posY);
    }
}
