# Sitegeist.Iconoclasm 

### Image optimization of images for Flow and Neos using the imagemin-cli tool. 

This package is inspired by MOC.ImageOptimizer https://packagist.org/packages/moc/imageoptimizer
and Sitegeist.Origami https://github.com/sitegeist/Sitegeist.Origami

Deviations from the previously mentioned packages:
- Use the imagemin cli tool and the plugins for that (other than Sitegeist.Origami and MOC.ImageOptimizer)
- Rely on async thumbnails instead of using a jobqueue (other than Sitegeist.Origami) 
- Use local temp files that are imported to support cloud storages (other than Sitegeist.Origami and MOC.ImageOptimizer)

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
RUN npm install --global imagemin-cli imagemin-pngquant imagemin-webp imagemin-mozjpeg
```

!!!Please verify the that the cli tools work by executing each plugin separately. Especially the imagemin-webp tool 
sometimes requires additional libraries!!!

## Configuration

Using the `Settings` configuration, multiple options can be adjusted.

Each optimization for a media-format has to be enabled exlicitly since by default
all optimizations are disabled.

```
Sitegeist:
  Iconoclasm:

    # 
    # The imagemin cli command, you may want to configure an explicit path here for your server 
    # 
    # The tool can be installed with npm:
    # `npm install --global imagemin-cli imagemin-pngquant imagemin-webp imagemin-mozjpeg imagemin-svgo`
    # 
    command: 'imagemin'

    #
    # The media types that are to be optimized
    #
    # each media type has to be enabled and and allows to configure additional cli options 
    # to adjust the used plugins or quality settings.
    # 
    mediaTypes:
      'image/jpeg':
        enabled: true
        options: '--plugin=mozjpeg'

      'image/png':
        enabled: true
        options: '--plugin=pngquant'

      'image/webp':
        enabled: true
        options: '--plugin=webp'

      'image/gif':
        enabled: true

      'image/svg':
        enabled: true
```

## Usage

* Clear thumbnails to generate new ones that will automatically be optimized.

`./flow media:clearthumbnails`

* Flush the Fusion Caches  

`./flow flow:cache:flushone Neos_Fusion_Content`

* See system log for debugging and error output.
