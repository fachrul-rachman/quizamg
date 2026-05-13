<?php

test('password confirmation routes are not enabled by default in this app', function () {
    expect(app('router')->has('password.confirm'))->toBeFalse();
});
