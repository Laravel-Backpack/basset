<?php

use Illuminate\Support\Str;

Blade::directive('loadStyleOnce', function ($parameter) {
    return "<?php Assets::echoCss({$parameter}); ?>";
});

Blade::directive('loadScriptOnce', function ($parameter) {
    return "<?php Assets::echoJs({$parameter}); ?>";
});

Blade::directive('loadOnce', function ($parameter) {
    // determine if it's a CSS or JS file
    $filePath = $parameter;
    $filePath = trim($filePath, "'");
    $filePath = trim($filePath, '"');
    $filePath = trim($filePath, '`');
    $filePath = Str::before($filePath, '?');
    $filePath = Str::before($filePath, '#');
    $extension = substr($filePath, -3);

    switch ($extension) {
        case 'css':
            return "<?php Assets::echoCss({$parameter}); ?>";
            break;

        case '.js':
            return "<?php Assets::echoJs({$parameter}); ?>";
            break;

        default:
            // it's a block start
            $parameter = trim($parameter, "'");
            $parameter = trim($parameter, '"');
            $parameter = trim($parameter, '`');

            return "<?php if(! Assets::isAssetLoaded('".$parameter."')) { Assets::markAssetAsLoaded('".$parameter."');  ?>";
            break;
    }
});

Blade::directive('endLoadOnce', function () {
    return '<?php } ?>';
});
