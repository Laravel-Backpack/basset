<?php

Blade::directive('loadCssOnce', function ($parameter) {
    //remove the single/double quotation marks from the parameter.
    $parameter = trim($parameter, "'");
    $parameter = trim($parameter, "'");

    //check if parameter is a variable and should be evaluated at run time.
    if (! substr($parameter, 0, 1) === '$') {
        return "<?php if(! Assets::isAssetLoaded('".$parameter."')) { Assets::markAssetAsLoaded('".$parameter."'); echo Assets::echoCssFileLink('".$parameter."'); } ?>";
    }

    return "<?php if(! Assets::isAssetLoaded({$parameter})) { Assets::markAssetAsLoaded({$parameter}); echo Assets::echoCssFileLink({$parameter}); } ?>";
});

Blade::directive('loadJsOnce', function ($parameter) {
    //remove the single/double quotation marks from the parameter.
    $parameter = trim($parameter, "'");
    $parameter = trim($parameter, "'");

    //check if parameter is a variable and should be evaluated at run time.
    if (! substr($parameter, 0, 1) === '$') {
        return "<?php if(! Assets::isAssetLoaded('".$parameter."')) { Assets::markAssetAsLoaded('".$parameter."'); echo Assets::echoJsFileLink('".$parameter."'); } ?>";
    }

    return "<?php if(! Assets::isAssetLoaded({$parameter})) { Assets::markAssetAsLoaded({$parameter}); echo Assets::echoJsFileLink({$parameter}); } ?>";
});

Blade::directive('loadOnce', function ($parameter) {
    $parameter = trim($parameter, "'");

    return "<?php if(! Assets::isAssetLoaded('".$parameter."')) { Assets::markAssetAsLoaded('".$parameter."');  ?>";
});

Blade::directive('endLoadOnce', function () {
    return '<?php } ?>';
});
