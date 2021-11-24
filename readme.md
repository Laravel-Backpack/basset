# Assets

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]

Replace your `<script src='file.js'>` and `<link href='file.css'>` tags with `@loadOnce('file.css')` and `@loadOnce('file.js')` and this package will make sure that CSS or JS will only be loaded one time per page.

## Installation

Via Composer

``` bash
$ composer require digitallyhappy/assets
```

## Usage

Replace your standard CSS and JS loading HTML with the `@loadOnce()` Blade directive this package provides:

```diff
-    <script src="{{ asset('path/to/file.js') }}">
+    @loadOnce('path/to/file.js')

-    <link href="{{ asset('path/to/file.css') }}" rel="stylesheet" type="text/css">
+    @loadOnce('path/to/file.css')
```

The package provides three Blade directives, in 99% of the cases you'll use `@loadOnce()`:

```php
@loadOnce('path/to/file.css')
@loadOnce('path/to/file.js')
// depending of the file extension, the first time it will output
// <link href="{{ asset('path/to/file.css')" rel="stylesheet" type="text/css">
// or
// <script src="{{ asset('path/to/file.js')"></script>
// then the rest of the times this is called... it'll output nothing

// IN ADDITION, if you have an entire block of HTML that you want to only output once:

@loadOnce('unique_name_for_code_block')
    <script>
        <!-- Your JS here -->
    </script>

    <!-- OR -->

    <style>
        <!-- Your CSS here -->
    </style>
@endLoadOnce
// will output the contents the first time...
// then the second time it will just output nothing
```

However, if you want to pass a _variable_ as the parameter, not a _string_, you'll notice it won't work, because the directive can't tell if it's a CSS, JS or code block. That's why we've created `@loadStyleOnce()` and `@loadScriptOnce()`:

```php
@php
    $pathToCssFile = 'path/to/file.css';
    $pathToJsFile = 'path/to/file.js';
@endphp

@loadStyleOnce($pathToCssFile)
// will output <link href="{{ asset('path/to/file.css')"> the first time
// then the second time this is called it'll output nothing

@loadScriptOnce($pathToJsFile)
// will output <script src="{{ asset('path/to/file.js')"></script> the first time
// then the second time this is called it'll output nothing
```

## Why does this package exist?

In Laravel 8+, if your CSS or JS assets are loaded inside a blade file:

```php
// card.blade.php

<div class="card">
  Lorem ipsum
</div>

<script src="path/to/script.js"></script>
```

And you load that blade file multiple times per page (eg. include `card.blade.php` multiple times per page), you'll end up with that `script` tag being loaded multiple times, on the same page. To avoid that, Larvel 8 provides [the `@once` directive](https://laravel.com/docs/8.x/blade#the-once-directive), which will echo the thing only once, no matter how many times that blade file loaded:

```php
// card.blade.php

<div class="card">
  Lorem ipsum
</div>

@once
<script src="path/to/script.js"></script>
@endonce
```

But what if your `script.js` file is not only loaded by `card.blade.php`, but also by other blade templates (eg. `hero.blade.php`, loaded on the same page? If you're using the `@once` directive, you will have the same problem all over again - that same script loaded multiple times.

That's where this package comes to the rescue. It will load the asset just ONCE, even if it's loaded from multiple blade files.

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email hello@tabacitu.ro instead of using the issue tracker.

## Credits

- [Cristian Tabacitu][link-author]
- [All Contributors][link-contributors]

## License

MIT. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/digitallyhappy/assets.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/digitallyhappy/assets.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/digitallyhappy/assets/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/421785142/shield

[link-packagist]: https://packagist.org/packages/digitallyhappy/assets
[link-downloads]: https://packagist.org/packages/digitallyhappy/assets
[link-travis]: https://travis-ci.org/digitallyhappy/assets
[link-styleci]: https://styleci.io/repos/421785142
[link-author]: https://github.com/digitallyhappy
[link-contributors]: ../../contributors
