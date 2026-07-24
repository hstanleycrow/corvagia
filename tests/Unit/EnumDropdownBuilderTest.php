<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Components\Dropdowns\EnumDropdownBuilder;

final class EnumDropdownBuilderTest extends TestCase
{
    private array $options = ['S' => 'Activo', 'N' => 'Inactivo'];

    public function test_build_renders_select_with_options_and_marks_selected(): void
    {
        $html = EnumDropdownBuilder::build('active', $this->options, 'S');

        $this->assertStringContainsString('id="active"', $html);
        $this->assertStringContainsString('name="active"', $html);
        $this->assertStringContainsString('<option value="S" selected>Activo</option>', $html);
        $this->assertStringContainsString('<option value="N" >Inactivo</option>', $html);
    }

    public function test_build_without_error_uses_plain_form_select_class(): void
    {
        $html = EnumDropdownBuilder::build('active', $this->options, 'S', false);

        $this->assertStringContainsString('class="form-select"', $html);
        $this->assertStringNotContainsString('is-invalid', $html);
    }

    public function test_build_with_error_appends_invalid_class(): void
    {
        $html = EnumDropdownBuilder::build('active', $this->options, 'S', true);

        $this->assertStringContainsString('class="form-select is-invalid"', $html);
    }

    public function test_build_with_selected_value_not_in_options_marks_nothing_selected(): void
    {
        $html = EnumDropdownBuilder::build('active', $this->options, 'X');

        $this->assertStringNotContainsString('selected', $html);
    }

    public function test_build_with_empty_options_renders_select_without_options(): void
    {
        $html = EnumDropdownBuilder::build('active', [], 'S');

        $this->assertStringContainsString('<select', $html);
        $this->assertStringContainsString('></select>', $html);
    }
}
