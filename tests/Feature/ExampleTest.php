<?php

test('should not open homepage', function () {
    $response = $this->get('/');

    $response->assertStatus(404);
});
