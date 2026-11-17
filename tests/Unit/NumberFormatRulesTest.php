<?php

namespace Tests\Unit;

use App\Support\NumberFormatRules;
use PHPUnit\Framework\TestCase;

class NumberFormatRulesTest extends TestCase
{
    public function test_it_accepts_custom_formats_written_by_the_school(): void
    {
        $this->assertNull(NumberFormatRules::error('class.number_format', 'T{year}/{shift}{grade}.{sequence}'));
        $this->assertNull(NumberFormatRules::error('student.number_format', 'EST_{year}_{sequence}'));
        $this->assertNull(NumberFormatRules::error('enrollment.number_format', '{year}{sequence}'));
        $this->assertNull(NumberFormatRules::error('teacher.number_format', 'DOC-{sequence}'));
    }

    public function test_it_requires_the_sequence_marker_exactly_once(): void
    {
        $this->assertStringContainsString('{sequence}', NumberFormatRules::error('class.number_format', 'TUR-{year}'));
        $this->assertNotNull(NumberFormatRules::error('class.number_format', '{sequence}-{sequence}'));
        $this->assertNotNull(NumberFormatRules::error('class.number_format', '   '));
    }

    public function test_it_rejects_markers_that_do_not_exist_for_the_record(): void
    {
        $this->assertStringContainsString('{grade}', NumberFormatRules::error('student.number_format', 'ALU-{grade}-{sequence}'));
        $this->assertStringContainsString('{ano}', NumberFormatRules::error('class.number_format', 'TUR-{ano}-{sequence}'));
    }

    public function test_it_rejects_unsafe_characters_and_numbers_longer_than_the_column(): void
    {
        $this->assertNotNull(NumberFormatRules::error('class.number_format', 'TURMA ÚNICA {sequence}'));
        $this->assertStringContainsString('20', NumberFormatRules::error('class.number_format', 'TURMA-ESCOLA-{year}-{grade}-{sequence}'));
    }

    public function test_it_previews_the_generated_number(): void
    {
        $this->assertSame('TUR-2026-01-01', NumberFormatRules::preview('class.number_format', 'TUR-{year}-{grade}-{sequence}'));
        $this->assertSame('01-MAN-01', NumberFormatRules::preview('class.number_format', '{grade}-{shift}-{sequence}'));
        $this->assertSame('MAT-2026-000001', NumberFormatRules::preview('enrollment.number_format', 'MAT-{year}-{sequence}'));
    }
}
