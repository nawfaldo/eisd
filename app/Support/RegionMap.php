<?php

namespace App\Support;

/**
 * Province outlines for Indonesia, Papua New Guinea and Malaysian Borneo.
 *
 * Geometry is pre-projected to SVG path data at build time (see resources/data/regions.json)
 * so nothing has to be projected at request time and the browser ships no mapping library.
 */
class RegionMap
{
    /**
     * @return array{width: int, height: int, regions: list<array{id: string, name: string, country: string, d: string}>}
     */
    public static function load(): array
    {
        return self::read('regions.json');
    }

    /**
     * Kabupaten/kota outlines, projected to the same frame as load() so the
     * two choropleths line up pixel for pixel.
     *
     * @return array{width: int, height: int, regions: list<array{id: string, name: string, province: ?string, country: string, d: string}>}
     */
    public static function cities(): array
    {
        return self::read('regions-kabupaten.json');
    }

    /** @var array<string, array<string, mixed>> */
    private static array $cache = [];

    /**
     * once() keys on the call site, so both loaders sharing this line would
     * collide. Cache by filename instead.
     *
     * @return array<string, mixed>
     */
    private static function read(string $file): array
    {
        return self::$cache[$file] ??= json_decode(
            file_get_contents(resource_path('data/'.$file)),
            associative: true,
            flags: JSON_THROW_ON_ERROR,
        );
    }
}
