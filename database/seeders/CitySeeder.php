<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the offline place database from the bundled GeoNames extract.
 *
 * Source: https://download.geonames.org/export/dump/ (Creative Commons
 * Attribution 4.0) — free to use and redistribute, no API key required.
 *
 * The extract combines:
 *   - every populated place in Nepal (~88,000 entries, down to village level)
 *   - all world cities above 15,000 population
 *
 * Columns: geonameid, name, asciiname, country, admin1, lat, lon, timezone, population
 */
class CitySeeder extends Seeder
{
    public function run(): void
    {
        // The extract is stored gzipped (2.4 MB instead of 8.4 MB) and
        // streamed through PHP's compression wrapper, so it never needs
        // to be decompressed to disk.
        $gzPath = storage_path('app/geo/cities.tsv.gz');
        $plainPath = storage_path('app/geo/cities.tsv');

        if (file_exists($gzPath)) {
            $handle = gzopen($gzPath, 'r');
            $reader = 'gzgets';
            $closer = 'gzclose';
        } elseif (file_exists($plainPath)) {
            $handle = fopen($plainPath, 'r');
            $reader = 'fgets';
            $closer = 'fclose';
        } else {
            $this->command->error("City data not found at {$gzPath}");

            return;
        }

        $this->command->info('Seeding cities from GeoNames extract...');

        DB::table('cities')->truncate();
        $batch = [];
        $count = 0;
        $now = now();

        while (($line = $reader($handle)) !== false) {
            $parts = explode("\t", rtrim($line, "\n\r"));

            if (count($parts) < 9) {
                continue;
            }

            [$geonameId, $name, $asciiName, $country, $admin1, $lat, $lon, $timezone, $population] = $parts;

            // A place without a timezone is useless to us — the whole
            // point of this table is resolving the birth offset.
            if ($timezone === '' || $lat === '' || $lon === '') {
                continue;
            }

            $batch[] = [
                'geoname_id' => (int) $geonameId,
                'name' => mb_substr($name, 0, 200),
                'ascii_name' => mb_substr($asciiName !== '' ? $asciiName : $name, 0, 200),
                'country_code' => mb_substr($country, 0, 2),
                'admin1' => mb_substr($admin1, 0, 20),
                'latitude' => (float) $lat,
                'longitude' => (float) $lon,
                'timezone' => mb_substr($timezone, 0, 64),
                'population' => (int) $population,
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($batch) >= 2000) {
                DB::table('cities')->insertOrIgnore($batch);
                $count += count($batch);
                $batch = [];
                $this->command->getOutput()->write("\r  Inserted {$count} places...");
            }
        }

        if ($batch !== []) {
            DB::table('cities')->insertOrIgnore($batch);
            $count += count($batch);
        }

        $closer($handle);

        $this->command->getOutput()->writeln('');
        $this->command->info("Seeded {$count} places.");
    }
}
