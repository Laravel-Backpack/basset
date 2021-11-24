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
    $cleanParameter = Str::of($parameter)->trim("'")->trim('"')->trim('`');
    $filePath = Str::of($cleanParameter)->before('?')->before('#');
    $extension = substr($filePath, -3);

    // mey be useful to get the second parameter
    // if (Str::contains($parameter, ',')) {
    //     $secondParameter = Str::of($parameter)->after(',')->trim(' ');
    // }

    switch ($extension) {
        case 'css':
            return "<?php Assets::echoCss({$parameter}); ?>";
            break;

        case '.js':
            return "<?php Assets::echoJs({$parameter}); ?>";
            break;

        default:
            // it's a block start

            return "<?php if(! Assets::isAssetLoaded('".$cleanParameter."')) { Assets::markAssetAsLoaded('".$cleanParameter."');  ?>";
            break;
    }
});

Blade::directive('endLoadOnce', function () {
    return '<?php } ?>';
});
