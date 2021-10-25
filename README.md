# Sitegeist.Iconoclasm 

### Image optimization of images for Flow and Neos using the imagemin-cli or other tools. 

This package is inspired by MOC.ImageOptimizer https://packagist.org/packages/moc/imageoptimizer
and Sitegeist.Origami https://github.com/sitegeist/Sitegeist.Origami that basically perform the same task. 

Deviations from the previously mentioned packages:
- All mediaTypes that shall be optimized have to be enabled explicitly. 
- Imagemin-cli is used by default for all enabled media types but can be replaced as needed.
- Does not need a jobqueue like Sitegeist.Origami did because async thumbnails are nowadays default.
- Local temporary files that are later imported should support cloud storages (not tested yet). 
- Temorary files with file extensions allow to use tools that rely on file extensions. 

### Authors & Sponsors

* Martin Ficzel - ficzel@sitegeist.de

*The development and the public-releases of this package is generously sponsored by our employer http://www.sitegeist.de.*

## Installation

Sitegeist.Iconoclasm is available via packagist. Just add "sitegeist/iconoclasm" to the require section of the 
composer.json or run `composer require sitegeist/iconoclasm`. We use semantic-versioning so every breaking change 
will increase the major-version number.

In addition to the Flow Package the imagemin-cli and all plugins or other tools have to be available on the server.
You can install the libraries globally using `npm`:

```
npm install --global imagemin-cli imagemin-pngquant imagemin-webp imagemin-mozjpeg
```

!!!Please verify the that the cli tools work by executing each plugin separately. Especially the imagemin-webp tool 
sometimes requires additional libraries!!!

## Configuration

Using the `Sitegeist.Iconoclasm` configuration, the used `command` can be configured. The `mediaTypes` section allows to 
enable certain types and allows to override the global `command` for this type.  

```
Sitegeist:
  Iconoclasm:

    #
    # The imagemin cli command, you may want to configure an explicit path here for your server
    #
    # The tool can be installed npm install --global imagemin-cli imagemin-pngquant imagemin-webp imagemin-mozjpeg imagemin-svgo
    #
    command: 'imagemin {input} > {output}'

    #
    # The media types that are to be optimized
    #
    # Each media type has to be enabled for optimizing. In case specific options or even 
    # an different command shall be used the global `command` can be overwritten for each type.
    #
    mediaTypes:
      'image/jpeg':
        enabled: true
        command: 'imagemin {input} --plugin=mozjpeg > {output}'

      'image/png':
        enabled: true
        command: 'imagemin {input} --plugin=pngquant > {output}'

      'image/webp':
        enabled: true
        command: 'imagemin {input} --plugin=webp > {output}'

      'image/gif':
        enabled: false

      'image/svg':
        enabled: false
```

## Usage

* Clear thumbnails to generate new ones that will automatically be optimized.

`./flow media:clearthumbnails`

* Flush the Fusion Caches  

`./flow flow:cache:flushone Neos_Fusion_Content`

* See system log for debugging and error output.
