<?php

namespace Miclf\StaticMap;

/**
 * Represent a map tile.
 */
class Tile
{
    /**
     * The X number of the tile.
     *
     * @var int
     */
    public $x;

    /**
     * The Y number of the tile.
     *
     * @var int
     */
    public $y;

    /**
     * The zoom level of the tile.
     *
     * @var int
     */
    public $zoom;

    /**
     * The tile provider to use.
     *
     * @var string
     */
    public $provider;

    /**
     * The potential subdomains used by the tile provider.
     *
     * @var array
     */
    protected $subdomains = ['a', 'b', 'c'];

    /**
     * Class constructor
     *
     * @param int     $x
     * @param int     $y
     * @param int     $zoom
     * @param string  $provider
     *
     * @return self
     */
    public function __construct(int $x, int $y, int $zoom, string $provider)
    {
        $this->x = $x;
        $this->y = $y;
        $this->zoom = $zoom;
        $this->provider = $provider;
    }

    /**
     * Get the full URL of the tile.
     *
     * @return string
     */
    public function getUrl()
    {
        $subdomain = $this->subdomains[
            ($this->x + $this->y) % count($this->subdomains)
        ];

        $url = str_replace(
            ['{x}', '{y}', '{z}', '{s}'],
            [$this->x, $this->y, $this->zoom, $subdomain],
            $this->provider
        );

        return $url;
    }

    /**
     * Alias of `static::getUrl()`
     */
    public function __toString()
    {
        return $this->getUrl();
    }
}
