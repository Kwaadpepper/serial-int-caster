<?php

use Tests\KotlinComparisonTest;

require_once './vendor/autoload.php';

(function () {
    $args = func_get_args()[0] ?? [];
    $args = is_array($args) ? $args : [];
    try {
        call_user_func_array(
            [KotlinComparisonTest::class, 'generateKotlinTestList'],
            $args
        );
    } catch (\Exception $e) {
        echo sprintf(
            'Un error has occured %s',
            $e->getMessage()
        );
    }
})(getopt('', ['lines:']));
