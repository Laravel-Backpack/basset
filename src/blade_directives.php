<?php

Blade::directive('asset', function ($parameter) {
    //remove the single/double quotation marks from the parameter to get to the file extension
    $extension = trim($parameter, "'");
    $extension = trim($extension, '"');
    $extension = trim($extension, '`');
    $extension = substr($extension, -3);

    switch ($extension) {
        case 'css':
            return "<?php Assets::echoCss({$parameter}); ?>";
            break;

        case '.js':
            return "<?php Assets::echoJs({$parameter}); ?>";
            break;

        default:
            abort(500, 'Could not automatically recognize '.$parameter.' as either a CSS or JS file. Please use @loadCssOnce() or @loadJsOnce() instead of @asset()');
            break;
    }
});

Blade::directive('loadCssOnce', function ($parameter) {
    return "<?php Assets::echoCss({$parameter}); ?>";
});

Blade::directive('loadJsOnce', function ($parameter) {
    return "<?php Assets::echoJs({$parameter}); ?>";
});

Blade::directive('loadOnce', function ($parameter) {
    $parameter = trim($parameter, "'");
    $parameter = trim($parameter, '"');
    $parameter = trim($parameter, '`');

    return "<?php if(! Assets::isAssetLoaded('".$parameter."')) { Assets::markAssetAsLoaded('".$parameter."');  ?>";
});

Blade::directive('endLoadOnce', function () {
    return '<?php } ?>';
});
