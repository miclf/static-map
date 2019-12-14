<?php

namespace Miclf\StaticMap;

/**
 * Represent the bounding box of the map plus some related pieces of data.
 */
class BoundingBox
{
    // Coordinates in decimal degrees.
    public $left;
    public $bottom;
    public $right;
    public $top;

    // Coordinates as ‘floating tile number’.
    public $leftTileIndex;
    public $bottomTileIndex;
    public $rightTileIndex;
    public $topTileIndex;

    // The number of OSM tiles that are (at least partly) covered
    // by the bounding box, horizontally and vertically.
    public $xTileCount;
    public $yTileCount;

    // Dimensions of the uncropped image, in pixels.
    // These will always be multiples of the tile size.
    public $uncroppedWidth;
    public $uncroppedHeight;

    // Horizontal and vertical offset of the bounding box/the cropped area
    // relative to the uncropped image. These offsets are both in pixels.
    public $leftOffset;
    public $topOffset;

    /**
     * Class constructor.
     *
     * @param  float  $latitude
     * @param  float  $longitude
     * @param  int    $zoom
     * @param  int    $width
     * @param  int    $height
     *
     * @return self
     */
    public function __construct(
        float $latitude,
        float $longitude,
        int $zoom,
        int $width,
        int $height
    ) {
        $converter = new Converter;

        // By using only the latitude, longitude, zoom and pixel dimensions
        // of the map, we will calculate its bounding box.
        // Reminder: the ‘latitude-longitude’ pair indicates the
        // point that is located at the very centre of the map.

        // We start by converting the coordinates from decimal degrees to a
        // ‘floating tile number’. For example, if the longitude would be
        // at 40% of tile number 527, then the longitude converted to
        // ‘tile number’ would be 527,4.

        $x = $converter->longitudeToTile($longitude, $zoom);
        $y = $converter->latitudeToTile($latitude, $zoom);

        // Then, we use the pixel dimensions of the map and convert them to a
        // number of tiles. For example, if the map width is 500px, it will
        // be equivalent to 1,953125 tiles (almost two tiles of 256px).

        $widthAsTileNumber = $width / StaticMap::TILE_SIZE;
        $heightAsTileNumber = $height / StaticMap::TILE_SIZE;

        $halfWidth = $widthAsTileNumber / 2;
        $halfHeight = $heightAsTileNumber / 2;

        // To find the left side of the bounding box, we remove half of the
        // map width from the position of the map centre. We then convert
        // the result back to decimal degrees. We finally do similar
        // operations for the other sides of the bounding box.

        $this->left   = $converter->tileToLongitude($x - $halfWidth, $zoom);
        $this->bottom = $converter->tileToLatitude($y + $halfHeight, $zoom);
        $this->right  = $converter->tileToLongitude($x + $halfWidth, $zoom);
        $this->top    = $converter->tileToLatitude($y - $halfHeight, $zoom);

        // Find the coordinates of the bounding box as ‘floating tile numbers’.
        $this->leftTileIndex = $converter->longitudeToTile($this->left, $zoom);
        $this->bottomTileIndex = $converter->latitudeToTile($this->bottom, $zoom);
        $this->rightTileIndex = $converter->longitudeToTile($this->right, $zoom);
        $this->topTileIndex = $converter->latitudeToTile($this->top, $zoom);

        // Get the horizontal and vertical offsets (in pixels) of
        // the bounding box relative to the uncropped image.
        $this->leftOffset = intval(
            ($this->leftTileIndex - floor($this->leftTileIndex)) * StaticMap::TILE_SIZE
        );
        $this->topOffset = intval(
            ($this->topTileIndex - floor($this->topTileIndex)) * StaticMap::TILE_SIZE
        );

        // Determine the number of OSM tiles that are (at least partly) covered
        // by the bounding box, horizontally and vertically. This will give us
        // the amount of tiles that we will have to download.
        $this->xTileCount = (int) $this->rightTileIndex - (int) $this->leftTileIndex + 1;
        $this->yTileCount = (int) $this->bottomTileIndex - (int) $this->topTileIndex + 1;

        // Calculate the dimensions of the uncropped image, in pixels.
        $this->uncroppedWidth = ($this->xTileCount) * StaticMap::TILE_SIZE;
        $this->uncroppedHeight = ($this->yTileCount) * StaticMap::TILE_SIZE;

        return $this;
    }
}
