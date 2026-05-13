<?php

use App\Services\GradeService;

it('maps score percentage to expected grade thresholds', function () {
    $service = new GradeService();

    expect($service->fromScorePercentage(80.0))->toMatchArray(['grade_letter' => 'A']);
    expect($service->fromScorePercentage(79.99))->toMatchArray(['grade_letter' => 'B']);

    expect($service->fromScorePercentage(65.0))->toMatchArray(['grade_letter' => 'B']);
    expect($service->fromScorePercentage(64.99))->toMatchArray(['grade_letter' => 'C']);

    expect($service->fromScorePercentage(50.0))->toMatchArray(['grade_letter' => 'C']);
    expect($service->fromScorePercentage(49.99))->toMatchArray(['grade_letter' => 'D']);

    expect($service->fromScorePercentage(35.0))->toMatchArray(['grade_letter' => 'D']);
    expect($service->fromScorePercentage(34.99))->toMatchArray(['grade_letter' => 'E']);
});

it('clamps score percentage into 0..100', function () {
    $service = new GradeService();

    expect($service->fromScorePercentage(-10.0))->toMatchArray(['grade_letter' => 'E']);
    expect($service->fromScorePercentage(999.0))->toMatchArray(['grade_letter' => 'A']);
});

