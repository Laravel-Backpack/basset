# Basset

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]

Replace your `<script src='file.js'>` and `<link href='file.css'>` tags with `@basset('file.js')` and this package will internalize the file and make sure that CSS or JS will only be loaded one time per page.

This package will internalize assets from CDN, local files and code blocks, it may also internalize zip files.
All the files are store on `public` disk by default (`base_path('\storage\app\public\bassets')`) but you may change this in your configs.

## Installation

1) Install the package via Composer

```bash
composer require backpack/basset
```

2) If you are using the default disk, you must create the symbolic link on public to storage.

```bash
php artisan storage:link
```

## Usage

Replace your standard CSS and JS loading HTML with the `@basset()` Blade directive this package provides:

```diff
-    <script src="{{ asset('path/to/file.js') }}">
+    @basset(base_path('path/to/file.js'))

-    <link href="{{ asset('path/to/file.css') }}" rel="stylesheet" type="text/css">
+    @basset(base_path('path/to/file.css'))

-    <script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js">
+    @basset('https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js')
```

The package provides 4 Blade directives, `@basset()`, `@bassetBlock()`, `@bassetArchive()`, `@bassetDirectory()`:

- Local files

```php
@basset(resource_path('assets/file.css'))
@basset(resource_path('assets/file.js'))
```
```html
<link href="http://localhost/storage/basset/resources/assets/file.css" rel="stylesheet" type="text/css">
<script src="http://localhost/storage/basset/resources/assets/file.js"></script>
```

- CDN

```php
@basset('https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js')
```
```html
<script src="http://localhost/storage/basset/cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js"></script>
```

- Code block

```php
@bassetBlock('example/path/file.js')
<script>
  alert('Backpack bassets!');
</script>
@endBassetBlock()
```
```html
<script src="http://localhost/storage/basset/example/path/file.js"></script>
```

- Archives (.zip/.tar.gz)

```php
@bassetArchive('https://github.com/author/package-dist/archive/refs/tags/1.0.0.zip', 'package-1.0.0')
@basset('package-1.0.0/plugin.min.js')
```
```html
<script src="http://localhost/storage/basset/package-1.0.0/plugin.min.js"></script>
```

- Local directories

```php
@bassetDirectory(resource_path('package-1.0.0/'), 'package-1.0.0')
@basset('package-1.0.0/plugin.min.js')
```
```html
<script src="http://localhost/storage/basset/package-1.0.0/plugin.min.js"></script>
```

Note that for the first page load with one or many new bassets it will take some time to internalize all the files, specially if they come from a CDN.

For that reason, once all your styles and scripts are under the basset directory, you may use `basset:internalize` to internalize all those files. If you ever need it, `basset:clear` will delete all the files.

```bash
# internalizes all the @basset 
php artisan basset:internalize
```
```bash
# clears the basset directory
php artisan basset:clear
```

In order to speed up the first page load on production, we recommend you to add `basset:internalize` command to the deploy script.

## Why does this package exist?

1) Keep a copy of the CDN dependencies on your side.

For many reasons you may want to avoid CDNs, CDNs may fail sometimes, the uptime is not 100%, or your app may need to work offline.

2) Forget about compiling your assets.

Most of the times backend developers end up messing around with npm and compiling dependencies. Backpack has been there, at some point we had almost 100Mb of assets on our main repo.
Basset will keep all that mess away from backend developers.

3) Avoid multiple loads of the same assets.

In Laravel, if your CSS or JS assets are loaded inside a blade file:

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

[ico-version]: https://img.shields.io/packagist/v/backpack/basset.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/backpack/basset.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/backpack/basset/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/421785142/shield

[link-packagist]: https://packagist.org/packages/backpack/basset
[link-downloads]: https://packagist.org/packages/backpack/basset
[link-travis]: https://travis-ci.org/backpack/basset
[link-styleci]: https://styleci.io/repos/421785142
[link-author]: https://github.com/Laravel-Backpack
[link-contributors]: ../../contributors
