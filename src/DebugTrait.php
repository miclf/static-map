<?php

namespace Miclf\StaticMap;

/**
 * Provide a ‘debug mode’.
 *
 * Strictly speaking, this trait does not really *need* to be a trait. It is
 * used as a way to put most of the debug-related stuff into another file…
 */
trait DebugTrait
{
    /**
     * An array of GD colors that will be used by debug mode.
     *
     * @var array
     */
    protected $colors = [];

    /**
     * Enable debug mode.
     *
     * @return self
     */
    public function debug(): self
    {
        $this->inDebugMode = true;

        return $this;
    }

    /**
     * Draw different types of debug stuff.
     *
     * @return void
     */
    protected function drawDebugData()
    {
        $this->defineColors();

        $this->drawTileGrid();
        $this->drawCropAreaOverlay();
        $this->drawCropArea();
        $this->drawReticule();
    }

    /**
     * Initialise GD colors that will be used by debug mode.
     *
     * @return void
     */
    protected function defineColors()
    {
        // Fully opaque colors.
        $this->colors['grid'] = imagecolorallocate($this->map, 0, 0, 255);
        $this->colors['reticule'] = imagecolorallocate($this->map, 0, 0, 0);
        $this->colors['cropped-area'] = imagecolorallocate($this->map, 0, 0, 0);

        // Partly transparent colors.
        $this->colors['label-bg'] =
            imagecolorallocatealpha($this->map, 255, 255, 255, 30);
        $this->colors['overlay'] =
            imagecolorallocatealpha($this->map, 0, 0, 0, 100);
    }

    /**
     * Draw the borders of individual map tiles, thus forming a grid.
     *
     * @return void
     */
    protected function drawTileGrid()
    {
        $tileCount = ($this->box->xTileCount) * ($this->box->yTileCount);
        $tileIndex = 1;

        // Loop on each row and column of tiles.
        for ($y = 0; $y < $this->box->yTileCount; $y++) {
            for ($x = 0; $x < $this->box->xTileCount; $x++) {

                $leftSide = $x * static::TILE_SIZE;
                $topSide  = $y * static::TILE_SIZE;

                // Draw a line at the top border of the current tile.
                //
                // Technically, it is one pixel too much to the top,
                // thus actually drawing the *bottom border* of the
                // tile above the current one. This technique is
                // useful to avoid borders to be drawn at the
                // edges of the map.
                imagedashedline(
                    $this->map,
                    $leftSide - 1, $topSide - 1,
                    $leftSide + static::TILE_SIZE -1, $topSide -1,
                    $this->colors['grid']
                );

                // Draw a line at the left border of the current tile.
                //
                // Technically, it is one pixel too much to the left,
                // thus actually drawing the *right border* of the
                // tile located on the left of the current one.
                // This technique is useful to avoid borders
                // to be drawn at the edges of the map.
                imagedashedline(
                    $this->map,
                    $leftSide - 1, $topSide - 1,
                    $leftSide - 1, $topSide + static::TILE_SIZE - 1,
                    $this->colors['grid']
                );

                // We will then write the tile number in the upper left corner.

                $label = "{$tileIndex}/{$tileCount}";

                if ($tileIndex === 1) {
                    $label = 'Tile '.$label;
                }

                // We first draw a light background to make
                // the text of the label easier to read.
                imagefilledrectangle(
                    $this->map,
                    $leftSide, $topSide,
                    $leftSide + ($tileIndex === 1 ? 110 : 60), $topSide + 25,
                    $this->colors['label-bg']
                );

                // Then, we write the label itself, with some padding.
                imagestring(
                    $this->map,
                    // The number of the built-in font to use.
                    $font = 5,
                    // The X and Y position to start the text at.
                    $leftSide + 10, $topSide + 5,
                    $label,
                    $this->colors['grid']
                );

                $tileIndex++;
            }
        }
    }

    /**
     * Draw a semi-transparent overlay to highlight the map area to crop.
     *
     * @return void
     */
    protected function drawCropAreaOverlay()
    {
        // We will draw a partly transparent area all around the zone that
        // should be cropped. The trick is to decompose this surrounding
        // area into 4 different rectangles that, together, will look
        // like a seamless area.
        // It will work like this. The numbers indicate the four
        // parts, and the ‘X’ indicate the area to crop.
        //
        // 1111111
        // 22XXX33
        // 4444444

        // 1. Part above the area to crop, covering the whole width of the map.
        imagefilledrectangle(
            $this->map,
            0, 0,
            $this->box->uncroppedWidth, $this->box->topOffset,
            $this->colors['overlay']
        );

        // 2. Part on the left.
        imagefilledrectangle(
            $this->map,
            0, $this->box->topOffset + 1,
            $this->box->leftOffset, $this->box->topOffset + $this->height,
            $this->colors['overlay']
        );

        // 3. Part on the right.
        imagefilledrectangle(
            $this->map,
            $this->box->leftOffset + $this->width, $this->box->topOffset + 1,
            $this->box->uncroppedWidth, $this->box->topOffset + $this->height,
            $this->colors['overlay']
        );

        // 4. Part on the bottom, also covering the whole width of the map.
        imagefilledrectangle(
            $this->map,
            0, $this->box->topOffset + $this->height + 1,
            $this->box->uncroppedWidth, $this->box->uncroppedHeight,
            $this->colors['overlay']
        );
    }

    /**
     * Draw the area to crop on the generated map image.
     *
     * @return void
     */
    protected function drawCropArea()
    {
        // Draw a rectangle around the area to crop.
        imagerectangle(
            $this->map,
            $this->box->leftOffset, $this->box->topOffset,
            $this->box->leftOffset + $this->width, $this->box->topOffset + $this->height,
            $this->colors['cropped-area']
        );

        // We will then write a label in the upper left corner. This label
        // will be put above a light background to make it easier to read.

        imagefilledrectangle(
            $this->map,
            $this->box->leftOffset + 1, $this->box->topOffset + 1,
            $this->box->leftOffset + 125, $this->box->topOffset + 25,
            $this->colors['label-bg']
        );
        imagestring(
            $this->map,
            // The number of the built-in font to use.
            $font = 5,
            // The X and Y position to start the text at.
            $this->box->leftOffset + 10, $this->box->topOffset + 5,
            "Cropped area",
            $this->colors['cropped-area']
        );
    }

    /**
     * Draw a reticule-like shape indicating the target coordinates of the map.
     *
     * @return void
     */
    protected function drawReticule()
    {
        // We start by drawing the circle at the centre of the reticule. It is
        // itself placed at the exact centre of the area to crop. GD does not
        // provide a function to draw a ‘circle’, so the trick is simply to
        // draw an arc with an angle of 360 degrees, which makes a circle.
        imagearc(
            $this->map,
            $centreX = $this->box->leftOffset + $this->width / 2,
            $centreY = $this->box->topOffset + $this->height / 2,
            // Width and height of the arc. They’re equal to make a circle.
            20, 20,
            // The start and end angles of the arc.
            0, 360,
            $this->colors['reticule']
        );

        // We then draw four lines around the circle, like this.
        // The `·` represents the circle we’ve just drawn.
        //
        //  1
        // 2·3
        //  4

        // 1. Top line.
        imageline(
            $this->map,
            $this->box->leftOffset + $this->width / 2,
            $this->box->topOffset + $this->height / 2 - 50,
            $this->box->leftOffset + $this->width / 2,
            $this->box->topOffset + $this->height / 2 - 5,
            $this->colors['reticule']
        );

        // 2. Left line.
        imageline(
            $this->map,
            $this->box->leftOffset + $this->width / 2 - 50,
            $this->box->topOffset + $this->height / 2,
            $this->box->leftOffset + $this->width / 2 - 5,
            $this->box->topOffset + $this->height / 2,
            $this->colors['reticule']
        );

        // 3. Right line.
        imageline(
            $this->map,
            $this->box->leftOffset + $this->width / 2 + 5,
            $this->box->topOffset + $this->height / 2,
            $this->box->leftOffset + $this->width / 2 + 50,
            $this->box->topOffset + $this->height / 2,
            $this->colors['reticule']
        );

        // 4. Bottom line.
        imageline(
            $this->map,
            $this->box->leftOffset + $this->width / 2,
            $this->box->topOffset + $this->height / 2 + 5,
            $this->box->leftOffset + $this->width / 2,
            $this->box->topOffset + $this->height / 2 + 50,
            $this->colors['reticule']
        );
    }
}
