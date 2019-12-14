<?php

namespace Miclf\StaticMap;

/**
 * Provide methods to convert coordinates to tile numbers and vice versa.
 *
 * These methods manipulate *float* tile numbers, not integers, in
 * order to offer more flexibility. Please keep in mind that these
 * floats have to be rounded down (using `floor()`) if you want to
 * use them to download OpenStreetMap tiles.
 *
 * The formulas in this file are directly inspired by the ones
 * provided on the OpenStreetMap wiki.
 *
 * @see https://wiki.openstreetmap.org/wiki/Slippy_map_tilenames#Derivation_of_tile_names
 * @see https://wiki.openstreetmap.org/wiki/Slippy_map_tilenames#PHP
 */
class Converter
{
    /**
     * Convert a longitude to an X tile number.
     *
     * @param  float     $longitude  A longitude, in decimal degrees.
     * @param  int|null  $zoom       A zoom level
     *
     * @return float
     */
    public function longitudeToTile(float $longitude, ?int $zoom = null)
    {
        $n = 2 ** $zoom;

        return (($longitude + 180) / 360) * $n;
    }

    /**
     * Convert an X tile number to a longitude, in decimal degrees.
     *
     * @param  float     $x     An X tile number
     * @param  int|null  $zoom  A zoom level
     *
     * @return float
     */
    public function tileToLongitude(float $x, ?int $zoom = null)
    {
        $n = 2.0 ** $zoom;

        $longitudeInDegrees = $x / $n * 360.0 - 180.0;

        return $longitudeInDegrees;
    }

    /**
     * Convert a latitude to a Y tile number.
     *
     * @param  float     $latitude  A latitude, in decimal degrees.
     * @param  int|null  $zoom      A zoom level
     *
     * @return float
     */
    public function latitudeToTile(float $latitude, ?int $zoom = null)
    {
        $n = 2 ** $zoom;

        $latitudeInRadians = deg2rad($latitude);

        return (
            1 -
            log(tan($latitudeInRadians) + (1 / cos($latitudeInRadians)))
            / pi()
        ) / 2 * $n;
    }

    /**
     * Convert a Y tile number to a latitude, in decimal degrees.
     *
     * @param  float     $y     A Y tile number
     * @param  int|null  $zoom  A zoom level
     *
     * @return float
     */
    public function tileToLatitude(float $y, ?int $zoom = null)
    {
        $n = 2.0 ** $zoom;

        $latitudeInRadians = atan(sinh(pi() * (1 - 2 * $y / $n)));

        return rad2deg($latitudeInRadians);
    }
}
