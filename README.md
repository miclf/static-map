# 🗺 StaticMap

A PHP package for generating static maps from map tile servers. Think of it as taking a screenshot of specific locations on OpenStreetMap.

## Requirements

This package uses the [GD library](https://www.php.net/manual/en/book.image.php). You probably already have it, since it’s almost always installed by default with PHP.

## Installation

You can install the package via the [Composer dependency manager](https://getcomposer.org/):

```bash
composer require miclf/static-map
```

## Usage

The most straightforward way to use the package is by providing coordinates (latitude and longitude) and the path where the generated map will be saved.

```php
<?php

use Miclf\StaticMap\StaticMap;

// This generates a map centred on the Atomium, in Brussels.
StaticMap::centeredOn(50.89492, 4.34152)->save('path/to/map.png');
```

You can also provide a specific zoom level to use:

```php
// Specify the zoom level as a third argument to the constructor.
StaticMap::centeredOn(50.89492, 4.34152, 10)->save('path/to/map.png');

// Alternatively, you can also specify a zoom level with a dedicated method.
StaticMap::centeredOn(50.89492, 4.34152)
    ->withZoom(10)
    ->save('path/to/map.png');
```

### Options

Several aspects of the generated maps can be customised.

#### Width and height of the image

```php
// Generates a map with a width of 500px and a height of 250px.
StaticMap::centeredOn(50.89492, 4.34152)
    ->withDimensions(500, 250)
    ->save('path/to/map.png');
```

#### Tile provider

By default, the maps are rendered using the [‘Terrain’ style from Stamen Design](http://maps.stamen.com/#terrain).
If, for example, you would like to use the tiles provided by [GEO-6](https://geo6.be/) for [OpenStreepMap Belgium](https://tile.osm.be) instead, use the `withTileProvider()` method to specify the URL template to use.

Please don’t forget to provide proper [attribution](https://www.openstreetmap.org/copyright/en) to the tile provider and the OSM contributors when using the generated maps. StaticMap is not able to do that for you.

```php
StaticMap::centeredOn(50.89492, 4.34152)
    ->withTileProvider('https://tile.openstreetmap.be/osmbe/{z}/{x}/{y}.png')
    ->save('path/to/map.png');
```

### ‘debug’ mode

Basically, StaticMap works by identifying which tiles are required for the map, depending on the coordinates and zoom level. It then downloads the tiles and groups them all in a single picture. Finally, it crops the image so that the map is centred on the specified latitude and longitude.

If you’re curious, you can enable a ‘debug’ mode to generate a map showing this process:

```php
StaticMap::centeredOn(50.89492, 4.34152)
    ->debug()
    ->save('path/to/map.png');
```

This mode has no real purpose apart from this educational aspect.

## License

License? Copyright? Putting copyright on code is like using pesticides in agriculture. Please don’t do that. That kills people.

This code belongs to the public domain. It belongs to everybody.

If you _really_ want a license, then this project is licensed under the [Creative Commons Zero license](https://creativecommons.org/publicdomain/zero/1.0/). Which means that it belongs to the public domain.
