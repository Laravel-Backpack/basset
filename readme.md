# Assets

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]

Replace your `<script src='file.js'>` and `<link href='file.css'>` tags with `@asset('file.css')` and this package will make sure that CSS or JS will only be loaded the first time it's called.

> THIS PACKAGE IS UNDER DEVELOPMENT AND NOT READY TO BE USED. STAY TUNED FOR AN OFFICIAL 1.0 RELEASE, THAT'S WHEN YOU'LL BE ABLE TO USE THIS IN YOUR PROJECTS. UNTIL THEN, DON'T! WE'RE STILL CHANGING DIRECTIVE NAMES AND STUFF.

## Installation

Via Composer

``` bash
$ composer require digitallyhappy/assets
```

## Usage

At this moment, the package provides a few Blade directives:

```php
@loadCssOnce('path/to/file.css')
// will output <link href="{{ asset('path/to/file.css')"> the first time
// then the second time this is called it'll output nothing

@loadJsOnce('path/to/file.js')
// will output <script src="{{ asset('path/to/file.js')"></script> the first time
// then the second time this is called it'll output nothing

@loadOnce
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

TODO:
- [ ] `@asset()` or `@loadAssetOnce()` that will determine the type of asset from its extension and load it appropriately
- [ ] maybe rename `@loadOnce` and `@endLoadOnce` with `@assetBlock` and `@endAssetBlock`
- [ ] add CDN assets to a queue, and provide a command for that queue to pull the asset from CDN, store it on the local drive (with a public link), and next time serve it from that local path instead of the public link

After TODOs, the docs will look like this:

```php

// FOR LOCAL ASSETS

@asset('path/to/file.css')
// will output <link href="{{ asset('path/to/file.css')"> the first time
// then the second time this is called it'll output nothing

@asset('path/to/file.js')
// will output <script src="{{ asset('path/to/file.js')"></script> the first time
// then the second time this is called it'll output nothing

@assetBlock
    <script>
        <!-- Your JS here -->
    </script>

    <!-- OR -->

    <style>
        <!-- Your CSS here -->
    </style>
@endAssetBlock
// will output the contents the first time...
// then the second time it will just output nothing

// FOR ASSETS FROM CDNs
@asset('https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js')
// will output <link href="{{ asset('https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js')"> the first time
// and it will add 'https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js' to a queue of files to be downloaded;
// after that queue has been run (you choose how often), the file has been "localized", which means it's
// also available in your /public/assets/localized/cdn.jsdelivr.net/npm/axios/dist/axios.min.js
// so this package will just load it from there instead, by outputting
// will output <link href="{{ asset('assets/localized/cdn.jsdelivr.net/npm/axios/dist/axios.min.js')">
//
// This has HUUUGE power, because now you can use CDN assets just like they were local. You do NOT have to
// provide them in your public directory, this package will do it for you.
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
