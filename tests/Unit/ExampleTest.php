<?php

test('that true is true', function () {
    expect(true)->toBeTrue();
});

test('checks basic math', function () {
    expect(1 + 1)->toBe(2);
});

test('works with arrays', function () {
    expect(['a', 'b', 'c'])->toContain('b')->toHaveCount(3);
});
