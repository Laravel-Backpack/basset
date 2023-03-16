<?php

dataset('paths', [
    'https://' => [
        'https://unpkg.com/vue@3/dist/vue.global.js',
        'unpkg.com/vue@3/dist/vue.global.js',
    ],
    'http://' => [
        'http://unpkg.com/vue@3/dist/vue.global.js',
        'unpkg.com/vue@3/dist/vue.global.js',
    ],
    '://' => [
        '://unpkg.com/vue@3/dist/vue.global.js',
        'unpkg.com/vue@3/dist/vue.global.js',
    ],
    'local' => [
        'unpkg.com/vue@3/dist/vue.global.js',
        'unpkg.com/vue@3/dist/vue.global.js',
    ],
    'invalid chars' => [
        'unpkg.com;/<vue@3>/:dist:/\'vue.global.js?*+`',
        'unpkg.com/vue@3/dist/vue.global.js',
    ],
]);
