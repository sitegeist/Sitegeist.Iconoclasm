# Sitegeist.Iconoclasm 

### Image optimization of images for Flow and Neos using the imagemin-cli tool or other tools. 

This package is inspired by MOC.ImageOptimizer https://packagist.org/packages/moc/imageoptimizer
and Sitegeist.Origami https://github.com/sitegeist/Sitegeist.Origami 

Deviations from the previously mentioned packages:
- Uses the imagemin cli tool by default for all types (other than Sitegeist.Origami and MOC.ImageOptimizer)
- Does not need a jobqueue like Sitegeist.Origami becauese async thaumbnails are nowadays default.
- Use local temporary files that are then imported to support cloud storages. 
- Use file extensions on temporary files to support tools that rely on those. 

### Authors & Sponsors

* Martin Ficzel - ficzel@sitegeist.de

*The development and the public-releases of this package is generously sponsored by our employer http://www.sitegeist.de.*

## Introduction

Neos CMS / Flow framework package that optimizes generated thumbnail images (jpg, png, gif, svg and more) for web presentation.
The original files of the editors are never affected since copies are always created for thumbnails.

The optimization is done with the imagemin cli-tool and a set of plugins for that. 

## Installation

Sitegeist.Iconoclasm is available via packagist. Just add "sitegeist/iconoclasm" to the require section of the 
composer.json or run `composer require sitegeist/iconoclasm`. We use semantic-versioning so every breaking change 
will increase the major-version number.

In addition to the Flow Package the imagemin-cli and plugins for the required formats are available on the server.
You can install the libraries globally using `npm`:

```
npm install --global imagemin-cli imagemin-pngquant imagemin-webp imagemin-mozjpeg
```

!!!Please verify the that the cli tools work by executing each plugin separately. Especially the imagemin-webp tool 
sometimes requires additional libraries!!!

## Configuration

Using the `Settings` configuration, multiple options can be adjusted.

Each optimization for a media-format has to be enabled explicitly since by default all optimizations are disabled.

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
