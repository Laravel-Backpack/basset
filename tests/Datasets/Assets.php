<?php

dataset('cdn', [
    'vue' => 'https://unpkg.com/vue@3/dist/vue.global.prod.js',
    'react' => 'https://unpkg.com/react@18/umd/react.production.min.js',
]);

dataset('local', [
    'vue' => 'resources/js/vue.global.prod.js',
    'react' => 'resources/js/react.production.min.js',
]);

dataset('codeBlock', [
    'codeBlock01.js',
    'codeBlock02.js',
    'codeBlock03.css',
]);

dataset('namedAssets', [
    [
        'vue',
        'https://unpkg.com/vue@3/dist/vue.global.prod.js',
        'https://unpkg.com/vue@3.1/dist/vue31.global.prod.js',
    ],
]);

dataset('namedAssetsOutput', [
    [
        'react',
        'https://unpkg.com/react@3/dist/reactscript.production.min.js',
    ],
]);
