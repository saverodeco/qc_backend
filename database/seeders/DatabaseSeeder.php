<?php

namespace Database\Seeders;

use App\Models\ReceivingIml;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class ReceivingImlSeeder extends Seeder
{
    /**
     * Vendor & material dummy — id_vendor/id_material di sini cuma angka
     * biasa (bukan foreign key ke tabel master), sesuai kolom yang ada
     * di receiving_iml sekarang.
     */
    private array $vendors = [
        ['id' => 1, 'nama' => 'PT Kertas Nusantara'],
        ['id' => 2, 'nama' => 'CV Sumber Pulp Jaya'],
        ['id' => 3, 'nama' => 'PT Andalas Paper Mill'],
        ['id' => 4, 'nama' => 'PT Mitra Selulosa'],
        ['id' => 5, 'nama' => 'CV Rimba Kertas'],
    ];

    private array $materials = [
        ['id' => 1, 'nama' => 'Kraft Liner 150gsm'],
        ['id' => 2, 'nama' => 'Corrugating Medium 120gsm'],
        ['id' => 3, 'nama' => 'Testliner 175gsm'],
        ['id' => 4, 'nama' => 'White Top Liner 200gsm'],
        ['id' => 5, 'nama' => 'Recycled Pulp Roll'],
    ];

    /**
     * Status yang benar-benar dipakai di aplikasi ini cuma 3: WAITING_QC,
     * QC_IN_PROGRESS, dan QC_DONE (selesai). Bobotnya dibuat rata-rata
     * merata biar layar Daftar Kendaraan QC kelihatan variatif.
     */
    private array $statusWeights = [
        'WAITING_QC' => 3,
        'QC_IN_PROGRESS' => 2,
        'QC_DONE' => 3,
    ];

    public function run(): void
    {
        $faker = fake('id_ID');

        // Sebagian besar data untuk HARI INI, supaya langsung kelihatan
        // begitu buka layar QC tanpa perlu ganti tanggal dulu.
        $this->generateForDate($faker, Carbon::today(), 15);

        // Sisanya disebar ke beberapa hari sekitar hari ini, buat nyoba
        // filter tanggal di layar Daftar Kendaraan.
        foreach ([-2, -1, 1, 2] as $offset) {
            $this->generateForDate($faker, Carbon::today()->addDays($offset), 5);
        }
    }

    private function generateForDate(\Faker\Generator $faker, Carbon $tanggalJanji, int $count): void
    {
        $weightedStatuses = $this->expandWeights($this->statusWeights);

        for ($i = 0; $i < $count; $i++) {
            $vendor = $faker->randomElement($this->vendors);
            $material = $faker->randomElement($this->materials);
            $status = $faker->randomElement($weightedStatuses);

            $isQcDone = $status === 'QC_DONE';

            ReceivingIml::create([
                'no_iml' => $this->generateNoIml($tanggalJanji, $i),
                'tanggal_janji' => $tanggalJanji->toDateString(),
                'id_vendor' => $vendor['id'],
                'vendor' => $vendor['nama'],
                'id_material' => $material['id'],
                'nama_material' => $material['nama'],
                'no_kendaraan' => $this->generateNopol($faker),
                'jumlah' => $faker->numberBetween(8, 24),
                'satuan' => 'Kg',
                // Ketiga status (WAITING_QC/QC_IN_PROGRESS/QC_DONE) berarti
                // kendaraan sudah tiba, jadi tanggal_kedatangan selalu diisi.
                'tanggal_kedatangan' => $tanggalJanji->copy()
                    ->addMinutes($faker->numberBetween(-30, 120))
                    ->toDateString(),
                'average_mc' => $isQcDone ? $faker->randomFloat(2, 8, 16) : null,
                'imp' => $isQcDone ? $faker->randomFloat(2, 0, 3) : null,
                'ot' => $isQcDone ? $faker->randomFloat(2, 0, 2) : null,
                // qc_by sengaja tidak diisi — operator QC tidak dicatat di aplikasi ini.
                'qc_at' => $isQcDone
                    ? $tanggalJanji->copy()->addHours($faker->numberBetween(1, 6))
                    : null,
                'status' => $status,
            ]);
        }
    }

    /**
     * Ubah ['STATUS' => bobot] jadi array datar berisi STATUS berulang
     * sesuai bobotnya, supaya bisa dipakai langsung dengan randomElement().
     */
    private function expandWeights(array $weights): array
    {
        $expanded = [];
        foreach ($weights as $status => $weight) {
            for ($i = 0; $i < $weight; $i++) {
                $expanded[] = $status;
            }
        }
        return $expanded;
    }

    private function generateNoIml(Carbon $tanggalJanji, int $index): string
    {
        // Format: YYMMDD + urutan 2 digit, contoh 2507150 -> unik per tanggal.
        return $tanggalJanji->format('ymd') . str_pad((string) ($index + 1), 3, '0', STR_PAD_LEFT);
    }

    private function generateNopol(\Faker\Generator $faker): string
    {
        $prefixes = ['B', 'D', 'Z', 'T', 'AB'];
        $prefix = $faker->randomElement($prefixes);
        $numbers = $faker->numberBetween(1000, 9999);
        $suffix = strtoupper($faker->lexify('??'));
        return "{$prefix} {$numbers} {$suffix}";
    }
}