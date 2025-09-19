<?php

declare(strict_types=1);

use App\Enums\Concerns\Conditionable;

// Test enum local to this test file
enum ConditionableSample: string
{
    use Conditionable;

    case RED = 'red';
    case BLUE = 'blue';
    case GREEN = 'green';
}

it('determines equality using is() with enum instance', function (): void {
    $value = ConditionableSample::RED;

    expect($value->is(ConditionableSample::RED))->toBeTrue()
        ->and($value->is(ConditionableSample::BLUE))->toBeFalse();
});

it('determines equality using is() with string value', function (): void {
    $value = ConditionableSample::BLUE;

    expect($value->is('blue'))->toBeTrue()
        ->and($value->is('red'))->toBeFalse();
});

it('returns false for is(null)', function (): void {
    expect(ConditionableSample::GREEN->is(null))->toBeFalse();
});

it('isNot is the negation of is', function (): void {
    $value = ConditionableSample::GREEN;

    expect($value->isNot('green'))->toBeFalse()
        ->and($value->isNot(ConditionableSample::GREEN))->toBeFalse()
        ->and($value->isNot('red'))->toBeTrue();
});

it('checks any of multiple values using isAny()', function (): void {
    $value = ConditionableSample::BLUE;

    // Mix of null, string and enum values
    expect($value->isAny(null, 'red', ConditionableSample::BLUE))->toBeTrue()
        ->and($value->isAny(null, 'green', ConditionableSample::RED))->toBeFalse();
});
