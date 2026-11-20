<?php

namespace Tests\Unit;

use App\Data\MoodProfile;
use PHPUnit\Framework\TestCase;

class MoodProfileTest extends TestCase
{
    public function test_labels_are_trimmed_deduplicated_and_capped(): void
    {
        $profile = MoodProfile::fromArray([
            'dayTime' => ' Dusk ',
            'moods' => [' Joy ', 'joy', '', 42, 'Hopeful', 'Cozy', 'Relaxed'],
            'relationship' => 'Friends',
            'genres' => ['Indie'],
        ]);

        $this->assertSame('Dusk', $profile->dayTime);
        $this->assertSame(['Joy', 'Hopeful', 'Cozy'], $profile->moods);
        $this->assertSame(['Indie'], $profile->genres);
    }

    public function test_a_single_label_or_a_missing_one_is_accepted(): void
    {
        $profile = MoodProfile::fromArray(['moods' => 'Sad']);

        $this->assertSame(['Sad'], $profile->moods);
        $this->assertSame([], $profile->genres);
    }
}
