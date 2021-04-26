CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

Image Annotator module allows users to create annotations on node images.

Users can highlight a particular portion of a node's image
by drawing a rectangle over the image and adding a note to it.

Adding, deleting, updating and viewing of these annotations can easily
be controlled by permission configuration.

It also supports multi-valued image fields.


REQUIREMENTS
------------

This module requires the following modules:
 * Image (Core)
 * Node (Core)

This module requires the Annotorious library:
https://github.com/annotorious/annotorious

Manual installation:
 * Download the library from:
   https://github.com/annotorious/annotorious/releases/tag/v0.6.4
 * Extract it in your 'libraries' folder, file paths should be:
   - DRUPAL_ROOT/libraries/annotorious/annotorious.min.js
   - DRUPAL_ROOT/libraries/annotorious/css/annotorious.css
   - DRUPAL_ROOT/libraries/annotorious/css/theme-dark/annotorious-dark.css

Composer installation:

1: Ensure that you have the `oomphinc/composer-installers-extender` package
   installed.
2: Add an "installer-types" section in the "extra" of your project composer.json
   file, make sure you have "bower-asset" and "npm-asset" listed.
   For example:
     "installer-types": [
         "bower-asset",
         "npm-asset"
     ],
3: In the "installer-paths" section in the "extra" of your project composer.json
 file, ensure you have the types drupal-library, bower-asset, and npm-asset.
   For example:
     "web/libraries/{$name}": [
         "type:drupal-library",
         "type:bower-asset",
         "type:npm-asset"
     ],
4: Add https://asset-packagist.org defined in your Composer repositories.
   For example:
    "repositories": {
        "asset-packagist": {
            "type": "composer",
            "url": "https://asset-packagist.org"
        },
        ...
5: Run 'composer require npm-asset/annotorious:0.6.4'.


INSTALLATION
------------

 * Install as you would normally install a contributed drupal module. See:
   https://drupal.org/documentation/install/modules-themes/modules-8
   for further information.


CONFIGURATION
-------------

 * Enable the Image Annotator module on your site.
 * Go to the configuration pages (admin/config/img_annotator) and choose
   your settings.
 * Go on a node's page with an image field with annotations enabled.
 * If you have the appropriate permissions you will be able to draw annotations.


MAINTAINERS
-----------

Current maintainers:
 * Florent Torregrosa (Grimreaper) - https://www.drupal.org/user/2388214

Previous maintainers:
 * abhaysaraf - https://www.drupal.org/user/2723079

This project has been sponsored by:
 * Smile - https://www.drupal.org/smile
   Sponsored maintenance.
