<?php

use App\Support\ParticipantWorkStartFormatter;

it('formats a work start date as an Indonesian month and year', function () {
    expect(ParticipantWorkStartFormatter::format('2023-01-01'))->toBe('Januari 2023');
});

it('uses a dash when the work start date is empty or invalid', function () {
    expect(ParticipantWorkStartFormatter::format(null))->toBe('-')
        ->and(ParticipantWorkStartFormatter::format(''))->toBe('-')
        ->and(ParticipantWorkStartFormatter::format('not-a-date'))->toBe('-');
});
