# Basset 🐶 - the better `asset()` helper for Laravel

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![StyleCI][ico-styleci]][link-styleci]

**Easily use your CSS/JS/etc assets from wherever they are, not just your public directory:**

```blade
{{-- if you're used to Laravel's asset helper: --}}
<link href="{{ asset('path/to/public/file.css') }}">

{{-- just change asset() to basset() and you can point to non-public files too, for example: --}}
<script src="{{ basset(storage_path('file.js')) }}">
<script src="{{ basset(base_path('vendor/org/package/assets/file.js')) }}">
<script src="{{ basset('https://cdn.com/path/to/file.js') }}">
```

That's all you need to do. **Basset will download the file to `storage/app/public/bassets` from wherever it is, then output the now-public path to your asset.**

Using Basset, you easily internalize and use:
- files from external URLs (like CDNs)
- files from internal, but non-public URLs (like the vendor directory)
- entire archives from external URLs (like GitHub)
- entire directories from local, non-public paths (like other local projects)

No more publishing package files. No more using NPM just to download some files. It's a simple yet effective solution in the age of `HTTP/2` and `HTTP/3`.

## Installation

```bash
composer require backpack/basset
php artisan basset:install
```

**Optional** publish the config file.
```bash
php artisan vendor:publish --provider="Backpack\Basset\BassetServiceProvider"
```

> **Note**  
> Basset is disabled by default on local environment. If you want to change it, please set `BASSET_DEV_MODE=false` in your env file.

#### Storage Symlink
Basset uses the `public` disk to store cached assets in a directory that is publicly-accessible. So it needs you to run `php artisan storage:link` to create the symlink. The installation command will create ask to run that, and to add that command to your `composer.json`. That will most likely make it work on your development/staging/production servers. If that's not the case, make sure you create the links manually wherever you need them, with the command `php artisan storage:link`.

#### Disk
By default Basset uses the `public` disk. If you're having trouble with the assets not showing up on page, you might have an old Laravel configuration for it. Please make sure your disk is properly setup on `config/filsystems.php` - it should look like [the default one](https://github.com/laravel/laravel/blob/10.x/config/filesystems.php#L39-L45).

## Usage

### The `basset()` Helper

You can just use the `basset()` helper instead of Laravel's `asset()` helper, and point to CDNs and non-public files too. Use [Laravel's path helpers](https://laravel.com/docs/10.x/helpers#paths-method-list) to construct the absolute path to your file, then Basset will take care of the rest.

For local from CDNs:
```blade
{{-- instead of --}}
<link href="{{ asset('path/to/public/file.css') }}">

{{-- you can do --}}
<link href="{{ basset('path/to/public/file.css' }}">
<link href="{{ basset('https://cdn.com/path/to/file.css') }}">
<link href="{{ basset(base_path('vendor/org/package/assets/file.css')) }}">
<link href="{{ basset(storage_path('file.css')) }}">
```

Basset will:
- copy that file from the vendor directory to your `storage` directory (aka. internalize the file)
- use the internalized file on all requests

### The `@basset()` Directive

For known asset types like CSS, JS, images and videos, among others, Basset makes it even shorter to load assets. No need to write the HTML for your `<script>`, `<link>` or `<img>`, just use the `@basset()` directive and all of the needed HTML will be output for you:

```blade
{{-- instead of --}}
<script src="{{ asset('path/to/public/file.js') }}">
<link href="{{ asset('path/to/public/file.css') }}">
<img src="{{ asset('path/to/public/file.jpg') }}">
<object data="{{ asset('path/to/public/file.pdf') }}"></object>

{{-- you can do --}}
@basset('https://cdn.com/path/to/file.js')
@basset('https://cdn.com/path/to/file.css')
@basset(resource_path('/path/to/file.jpg'))
@basset(resource_path('/path/to/file.pdf'))
```

These are the know file types;

| File extension | HTML element |
| --- | --- |
| `.js` | `<script>` |
| `.css` | `<style>` |
| `.jpg` `.jpeg` `.png` `.webp` `.gif` `.svg` | `<img>` |
| `.mp4` `.webm` `.avi` `.mp3` `.ogg` `.wav` | `<source>` |
| `.ico` | `<link>` |
| `.pdf` | `<object>` |
| `.vtt` | `<track>` |


Basset will:
- copy that file from the vendor directory to your `storage` directory (aka. internalize the file)
- use the internalized file on all requests
- make sure that file is only loaded once per pageload

### The `@bassetBlock()` Directive

Easily move code blocks to files, so they're cached

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

### The `@bassetArchive()` Directive

Easily use archived assets (.zip & .tar.gz):

```diff
+    @bassetArchive('https://github.com/author/package-dist/archive/refs/tags/1.0.0.zip', 'package-1.0.0')
+    @basset('package-1.0.0/plugin.min.js')
```

Basset will:
- download the archive to your `storage/app/public/basset` directory (aka. internalize the code)
- unarchive it
- on all requests, use the local file (using `<script src="">`)
- make sure that file is only loaded once per pageload

*Note:* when referencing `.zip` archives, the [PHP zip extension](https://www.php.net/manual/en/book.zip.php) is required.

### The `@bassetDirectory()` Directive

Easily internalize and use entire non-public directories:

```diff
+    @bassetDirectory(resource_path('package-1.0.0/'), 'package-1.0.0')
+    @basset('package-1.0.0/plugin.min.js')
```

Basset will:
- copy the directory to your `storage/app/public/basset` directory (aka. internalize the code)
- on all requests, use the internalized file (using `<script src="">`)
- make sure that file is only loaded once per pageload

### The `basset` Commands

Copying an asset from CDNs to your server could take a bit of time, depending on the asset size. For large pages, that could even take entire seconds. You can easily prevent that from happening, by internalizing all assets in one go. You can use `php artisan basset:cache` to go through all your blade files, and internalize everything that's possible. If you ever need it, `basset:clear` will delete all the files.

```bash 
php artisan basset:cache         # internalizes all @bassets
php artisan basset:clear         # clears the basset directory
```

In order to speed up the first page load on production, we recommend you to add `php artisan basset:cache` command to your deploy script.

### Basset Cached Event

If you require customized behavior after each asset is cached, you can set up a listener for the `BassetCachedEvent` in your `EventServiceProvider`. This event will be triggered each time an asset is cached.

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

(optional) Before you deploy to Vapor, you might want to set up S3 on localhost to test that it's working. If you do, [the steps here](https://github.com/Laravel-Backpack/basset/pull/58#issuecomment-1622125991) might help. If you encounter problems with deployment on Vapor (particularly through GitHub actions) there are some [tips here](https://github.com/Laravel-Backpack/basset/pull/58#issuecomment-1622125991).

### FTP / SFTP / ZIP

If you deploy your project by uploading it from localhost (either manually or automatically), you should:
- make sure the alias exists that would have been created by `php artisan storage:link`; otherwise your alias might point to an inexisting localhost path; alternatively you can change the disk that Basset is using, in its config;
- before each deployment, make sure to disable dev mode (`do BASSET_DEV_MODE=false` in your `.ENV` file) then run `php artisan basset:fresh`; that will make sure your localhost downloads all assets, then you upload them in your zip;


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

## FAQ

#### Basset is not working, what may be wrong?

Before making any changes, you can run the command `php artisan basset:check`. It will perform a basic test to initialize, write, and read an asset, giving you better insights into any errors.

The most common reasons for Basset to fail are:

1) **Incorrect APP_URL in the `.env` file.**  
Ensure that APP_URL in your `.env` matches your server configuration, including the hostname, protocol, and port number. Incorrect settings can lead to asset loading issues.

2) **Improperly configured disk.**  
By default, Basset uses the Laravel `public` disk. 
For new Laravel projects, the configuration is usually correct. 
If you're upgrading a project and/or changed the `public` disk configuration, it's advised that you change the basset disk in `config/backpack/basset.php` to `basset`. The `basset` disk is a copy of the original Laravel `public` with working configurations.

3) **Missing or broken storage symlink.**  
If you use the default `public` disk, Basset requires that the symlink between the storage and the public accessible folder to be created with `php artisan storage:link` command. During installation, Basset attempts to create the symlink. If it fails, you will need to manually create it with `php artisan storage:link`. If you encounter issues (e.g., after moving the project), recreating the symlink should resolve them.

Note for Homestead users: the symlink can't be created inside the virtual machine. You should stop your instance with: `vagrant down`, create the symlink in your local application folder and then `vagrant up` to bring the system back up. 

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
