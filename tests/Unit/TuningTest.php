<?php

namespace Tests\Unit;

use App\Data\Tuning;
use PHPUnit\Framework\TestCase;

class TuningTest extends TestCase
{
    public function test_values_are_clamped_and_snapped_to_slider_steps(): void
    {
        $tuning = Tuning::fromArray(['energy' => -10, 'popularity' => 133, 'instrumentalness' => 82.6]);

        $this->assertSame([0, 100, 85], [$tuning->energy, $tuning->popularity, $tuning->instrumentalness]);
    }

    public function test_missing_or_invalid_values_fall_back_to_the_defaults(): void
    {
        $tuning = Tuning::fromArray(['energy' => 'loud']);
        $defaults = new Tuning;

        $this->assertEquals($defaults, $tuning);
    }
}
