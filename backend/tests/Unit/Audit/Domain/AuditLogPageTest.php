<?php

namespace Tests\Unit\Audit\Domain;

use App\Audit\Domain\AuditLogPage;
use PHPUnit\Framework\TestCase;

class AuditLogPageTest extends TestCase
{
    public function test_create_with_items(): void
    {
        $now = new \DateTimeImmutable;
        $page = new AuditLogPage(
            items: [],
            nextCursorCreatedAt: $now,
            nextCursorInternalId: 10,
            hasMore: true,
        );

        $this->assertSame([], $page->items);
        $this->assertEquals($now, $page->nextCursorCreatedAt);
        $this->assertSame(10, $page->nextCursorInternalId);
        $this->assertTrue($page->hasMore);
    }

    public function test_empty_returns_page_with_no_more(): void
    {
        $page = AuditLogPage::empty();

        $this->assertSame([], $page->items);
        $this->assertNull($page->nextCursorCreatedAt);
        $this->assertNull($page->nextCursorInternalId);
        $this->assertFalse($page->hasMore);
    }
}
