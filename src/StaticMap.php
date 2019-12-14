<?php

namespace Miclf\StaticMap;

use Exception;

/**
 * Allow to generate static maps from OpenStreetMap tile servers.
 */
class StaticMap
{
    use DebugTrait;

    /**
     * The GD resource used to create the map image.
     *
     * @var resource
     */
    protected $map;

    /**
     * The latitude to center the map on, in decimal degrees.
     *
     * @var float
     */
    protected $latitude;

    /**
     * The longitude to center the map on, in decimal degrees.
     *
     * @var float
     */
    protected $longitude;

    /**
     * The zoom level of the map.
     *
     * @var int
     */
    protected $zoom = 17;

    /**
     * The width of the generated map, in pixels.
     *
     * @var int
     */
    protected $width = 486;

    /**
     * The height of the generated map, in pixels.
     *
     * @var int
     */
    protected $height = 300;

    /**
     * The bounding box of the map, in decimal degrees.
     *
     * This is the bounding box of the area we want.
     *
     * @var float[]
     */
    protected $box;

    /**
     * The tile provider to use.
     *
     * By default, we use the ‘Terrain’ style from Stamen
     * Design, which covers the whole world map.
     *
     * @see http://maps.stamen.com/#terrain
     *
     * @var string
     */
    protected $provider = 'http://tile.stamen.com/terrain/{z}/{x}/{y}.png';

    /**
     * Whether debug mode is enabled or not.
     *
     * In debug mode, the map does not get cropped.
     * Moreover, additional information is drawn:
     *   - tile limits and tile indices.
     *   - the area that should have been cropped is highlighted.
     *   - a reticule-like shape indicates the target coordinates of the map.
     *
     * @var bool
     */
    protected $inDebugMode = false;

    /**
     * The dimensions of the map tiles, in pixels.
     */
    const TILE_SIZE = 256;

    /**
     * Named constructor allowing to provide coordinates and zoom level.
     *
     * @param  float     $latitude   Latitude, in decimal degrees
     * @param  float     $longitude  Longitude, in decimal degrees
     * @param  int|null  $zoom       The zoom level of the map
     *
     * @return self
     */
    public static function centredOn(
        float $latitude,
        float $longitude,
        ?int $zoom = null
    ): self {
        $instance = new self;

        $instance->latitude = $latitude;
        $instance->longitude = $longitude;

        if ($zoom) {
            $instance->zoom = $zoom;
        }

        return $instance;
    }

    /**
     * Named constructor allowing to provide coordinates and zoom level.
     *
     * This is an alias of the `centredOn()` named constructor.
     *
     * @param  float     $latitude   Latitude, in decimal degrees
     * @param  float     $longitude  Longitude, in decimal degrees
     * @param  int|null  $zoom       The zoom level of the map
     *
     * @return self
     */
    public static function centeredOn(...$args): self {
        return static::centredOn(...$args);
    }

    /**
     * Specify the zoom level to use for the generated map.
     *
     * @param  int  $zoom
     *
     * @return self
     */
    public function withZoom(int $zoom): self
    {
        $this->zoom = $zoom;

        return $this;
    }

    /**
     * Specify the dimensions to use for the generated map.
     *
     * @param  int  $width   Width of the map to generate, in pixels
     * @param  int  $height  Height of the map, in pixels
     *
     * @return self
     */
    public function withDimensions(int $width, int $height): self
    {
        $this->width = $width;
        $this->height = $height;

        return $this;
    }

    /**
     * Set the tile provider to use for the generated map.
     *
     * @param  string  $provider
     *
     * @return self
     */
    public function withTileProvider(string $provider): self
    {
        $this->provider = $provider;

        return $this;
    }

    /**
     * Generate and save the image of the map to the given location
     *
     * @param  string  $filename
     *
     * @return void
     */
    public function save(string $filename): void
    {
        $this->generateMap();

        imagePNG($this->map, $filename);
    }

    /**
     * Generate the map.
     *
     * @return void
     */
    protected function generateMap()
    {
        $this->ensureMapHasDimensions();

        // Calculate the bounding box and other useful coordinates…
        $this->box = new BoundingBox(
            $this->latitude,
            $this->longitude,
            $this->zoom,
            $this->width,
            $this->height
        );

        // Create a blank image that will then be filled with tiles.
        $this->map = imagecreatetruecolor(
            $this->box->uncroppedWidth,
            $this->box->uncroppedHeight
        );

        // Loop on each row and column of tiles.
        foreach ($this->requiredYTiles() as $y => $yNumber) {
            foreach ($this->requiredXTiles() as $x => $xNumber) {

                $tile = new Tile($xNumber, $yNumber, $this->zoom, $this->provider);

                // Copy the current tile on to the map image.
                imagecopy(
                    $this->map,
                    $this->downloadTile($tile),
                    $dst_x = $x * static::TILE_SIZE,
                    $dst_y = $y * static::TILE_SIZE,
                    $src_x = 0,
                    $src_y = 0,
                    $src_w = static::TILE_SIZE,
                    $src_h = static::TILE_SIZE
                );
            }
        }

        if ($this->inDebugMode) {
            $this->drawDebugData();
        } else {
            $this->cropMap();
        }
    }

    /**
     * Download the image of a given tile and create an image resource from it.
     *
     * @param  \Miclf\StaticMap\Tile  $tile
     *
     * @return resource
     */
    protected function downloadTile(Tile $tile)
    {
        $url = $tile->getUrl();
        $extension = mb_substr($url, -4);

        if ($extension === '.png') {
            return imagecreatefrompng($url);
        } elseif ($extension === '.jpg') {
            return imagecreatefromjpeg($url);
        }

        // If we arrive here, it means that the image is not supported.
        // This kind of error is *very* unlikely to happen, except
        // if the developer made a typo when copying the URL.
        $lastDotPosition = mb_strrpos($url, '.');
        $extension = mb_substr($url, $lastDotPosition);

        throw new Exception(
            "Tile provider URL templates ending in “{$extension}” are ".
            "not supported. The URL must end with “.png” or “.jpg”."
        );
    }

    /**
     * Crop the generated image according to the map’s bounding box.
     *
     * @return void
     */
    protected function cropMap()
    {
        $this->map = imagecrop($this->map, [
            'x' => $this->box->leftOffset,
            'y' => $this->box->topOffset,
            'width' => $this->width,
            'height' => $this->height,
        ]);
    }

    /**
     * Get the range of X tile numbers of the tiles we need to request.
     *
     * @return int[]
     */
    protected function requiredXTiles(): array
    {
        // To get valid tile names, the limits of the bounding box have
        // to be rounded down to the nearest integers.
        return range(
            floor($this->box->leftTileIndex),
            floor($this->box->rightTileIndex)
        );
    }

    /**
     * Get the range of Y tile numbers of the tiles we need to request.
     *
     * @return int[]
     */
    protected function requiredYTiles(): array
    {
        // To get valid tile names, the limits of the bounding box have
        // to be rounded down to the nearest integers.
        return range(
            floor($this->box->topTileIndex),
            floor($this->box->bottomTileIndex)
        );
    }

    /**
     * Check if dimensions have been specified and throw an exception otherwise.
     *
     * @return void
     */
    protected function ensureMapHasDimensions(): void
    {
        if (!$this->width || !$this->height) {
            throw new Exception(
                'You must provide dimensions in order to save the map. '.
                'You can use the "withDimensions($width, $height)" method to do so.'
            );
        }
    }
}
