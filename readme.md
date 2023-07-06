# Basset 🐶

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]

**The dead-simple way to use CSS and JS assets in your Laravel projects.** 

```blade
// instead of
<script src='https://cdn.com/path/to/file.js'>
<link href='https://cdn.com/path/to/file.css'>

// you can do
@basset('https://cdn.com/path/to/file.js')
@basset('https://cdn.com/path/to/file.css')
```

That will internalize the file (copy to `storage/app/public/bassets`) and make sure that file is only loaded once per page. 

Using Basset, you easily internalize and use:
- files from external URLs (like CDNs)
- files from internal, but non-public URLs (like the vendor directory)
- entire archives from external URLs (like Github)
- entire directories from local, non-public paths (like other local projects)

## Installation

```bash
composer require backpack/basset
php artisan basset:install
```

#### Storage Symlink
Basset uses the `public` disk to store cached assets in a directory that is publicly-accesible. So it needs you to run `php artisan storage:link` to create the symlink. The installation command will create ask to run that, and to add that command to your `composer.json`. That will most likely make it work on your development/staging/production servers. If that's not the case, make sure you create the links manually wherever you need them, with the command `php artisan storage:link`.

#### Disk
By default Basset uses the `public` disk. If you're having trouble with the assets not showing up on page, you might have an old Laravel configuration for it. Please make sure your disk is properly setup on `config/filsystems.php` - it should look like [the default one](https://github.com/laravel/laravel/blob/10.x/config/filesystems.php#L39-L45).

## Usage

Now you can replace your standard CSS and JS loading HTML with the `@basset()` Blade directive:

```diff
// for files from CDNs
-    <script src="https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js">
+    @basset('https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js')
```

Basset will:
- copy that file from the vendor directory to your `storage` directory (aka. internalize the file)
- use the internalized file on all requests
- make sure that file is only loaded once per pageload

> **Note**  
> Basset is disabled by default on local environment (`APP_ENV=local`), if you want to change it please set `BASSET_DEV_MODE=false` on the env file.


## Configuration

Take a look at [the config file](https://github.com/Laravel-Backpack/basset/blob/main/src/config/backpack/basset.php) for all configuration options. Notice some of those configs also have ENV variables, so you can:
- enable/disable dev mode using `BASSET_DEV_MODE=false` - this will force Basset to internalize assets even on localhost
- change the disk where assets get internalized using `BASSET_DISK=yourdiskname`
- disable the cache map using `BASSET_CACHE_MAP=false` (needed on serverless like Laravel Vapor)

## Deployment

There are a lot of deployment options for Laravel apps, but we'll try to cover the gotchas of the most popular ones here:

### VPS / SSH / Composer available

- it is mandatory to run `php artisan storage:link` in production, for Basset to work; so it's recommended you add that to your `composer.json`'s scripts section, either under `post-composer-install` or `post-composer-update`;
- it is recommended to run `php artisan basset:fresh` after each deployment; so it's recommended you add that to your `composer.json`'s scripts section, either under `post-composer-update`;

### Laravel Forge

It's just a managed VPS, so please see the above.

### Laravel Vapor

**Step 1.** In your `vapor.yml` include `storage: yourbucketname`

**Step 2.** In your Vapor `.ENV` file make sure you have
```
BASSET_DISK=s3
BASSET_CACHE_MAP=false
```

(optional) Before you deploy to Vapor, you might want to set up S3 on localhost to test that it's working. If you do, [the steps here](https://github.com/Laravel-Backpack/basset/pull/58#issuecomment-1622125991) might help. If you encounter problems with deployment on Vapor (particularly through Github actions) there are some [tips here](https://github.com/Laravel-Backpack/basset/pull/58#issuecomment-1622125991).

### FTP / SFTP / ZIP

If you deploy your project by uploading it from localhost (either manually or automatically), you should:
- make sure the alias exists that would have been created by `php artisan storage:link`; otherwise your alias might point to an inexisting localhost path; alternatively you can change the disk that Basset is using, in its config;
- before each deployment, make sure to disable dev mode (`do BASSET_DEV_MODE=false` in your `.ENV` file) then run `php artisan basset:fresh`; that will make sure your localhost downloads all assets, then you upload them in your zip;


## Features

The package provides 4 Blade directives, `@basset()`, `@bassetBlock()`, `@bassetArchive()`, `@bassetDirectory()`, that will allow you to:

### Easily self-host files from CDNs

```diff
-   <script src="http://localhost/storage/basset/cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js"></script>
+   @basset('https://cdn.ckeditor.com/ckeditor5/36.0.1/classic/ckeditor.js')
```

Basset will:
- copy that file from the CDN to your `storage/app/public/basset` directory (aka. internalize the file)
- use the local (internalized) file on all requests
- make sure that file is only loaded once per pageload

### Easily use local files from non-public directories

```diff
+ @basset(resource_path('assets/file.css'))
+ @basset(resource_path('assets/file.js'))
```

Basset will:
- copy the file from the provided path to `storage/app/public/basset` directory (aka. internalize the file)
- use the internalized file on all requests
- make sure that file is only loaded once per pageload

### Easily move code blocks to files, so they're cached

```diff
+   @bassetBlock('path/or/name-i-choose-to-give-this')
    <script>
      alert('Do stuff!');
    </script>
+   @endBassetBlock()
```

Basset will:
- create a file with that JS code in your `storage/app/public/basset` directory (aka. internalize the code)
- on all requests, use the local file (using `<script src="">`) instead of having the JS inline
- make sure that file is only loaded once per pageload

### Easily use archived assets (.zip & .tar.gz)

```diff
+    @bassetArchive('https://github.com/author/package-dist/archive/refs/tags/1.0.0.zip', 'package-1.0.0')
+    @basset('package-1.0.0/plugin.min.js')
```

Basset will:
- download the archive to your `storage/app/public/basset` directory (aka. internalize the code)
- unarchive it
- on all requests, use the local file (using `<script src="">`)
- make sure that file is only loaded once per pageload

### Easily internalize and use entire non-public directories

```diff
+    @bassetDirectory(resource_path('package-1.0.0/'), 'package-1.0.0')
+    @basset('package-1.0.0/plugin.min.js')
```

Basset will:
- copy the directory to your `storage/app/public/basset` directory (aka. internalize the code)
- on all requests, use the internalized file (using `<script src="">`)
- make sure that file is only loaded once per pageload

### Easily internalize everything from the CLI, using a command

Copying an asset from CDNs to your server could take a bit of time, depending on the asset size. For large pages, that could even take entire seconds. You can easily prevent that from happening, by internalizing all assets in one go. You can use `php artisan basset:internalize` to go through all your blade files, and internalize everything that's possible. If you ever need it, `basset:clear` will delete all the files.

```bash 
php artisan basset:cache         # internalizes all @bassets
php artisan basset:clear         # clears the basset directory
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

Please see the [releases tab](https://github.com/Laravel-Backpack/basset/releases) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email hello@backpackforlaravel.com instead of using the issue tracker.

## Credits

- [Antonio Almeida](https://github.com/promatik)
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
