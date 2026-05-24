<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class AuditStorageImages extends Command
{
    protected $signature = 'storage:audit-images
        {--move : Pindahkan file tidak terpakai ke storage/app/public/_unused_images}
        {--delete : Hapus permanen file tidak terpakai}
        {--limit=100 : Batas tampilan file di terminal}';

    protected $description = 'Audit gambar storage public: cek file terpakai, tidak terpakai, dan format non-WebP.';

    public function handle(): int
    {
        $disk = Storage::disk('public');
        $allFiles = collect($disk->allFiles())
            ->filter(fn($file) => preg_match('/\.(jpg|jpeg|png|webp)$/i', $file))
            ->values();

        $usedPaths = $this->collectUsedImagePaths();

        $unusedFiles = $allFiles
            ->reject(fn($file) => $usedPaths->contains($file))
            ->reject(fn($file) => str_starts_with($file, '_unused_images/'))
            ->values();

        $nonWebpUsed = $allFiles
            ->filter(fn($file) => $usedPaths->contains($file))
            ->reject(fn($file) => str_ends_with(strtolower($file), '.webp'))
            ->values();

        $this->info('Audit selesai.');
        $this->line('Total file gambar di storage: ' . $allFiles->count());
        $this->line('File gambar masih dipakai DB: ' . $usedPaths->intersect($allFiles)->count());
        $this->line('File gambar tidak terpakai: ' . $unusedFiles->count());
        $this->line('File terpakai tapi belum WebP: ' . $nonWebpUsed->count());

        $limit = (int) $this->option('limit');

        if ($unusedFiles->isNotEmpty()) {
            $this->newLine();
            $this->warn('Contoh file tidak terpakai:');
            $unusedFiles->take($limit)->each(fn($file) => $this->line('- ' . $file));
        }

        if ($nonWebpUsed->isNotEmpty()) {
            $this->newLine();
            $this->warn('File masih dipakai tapi belum WebP:');
            $nonWebpUsed->take($limit)->each(fn($file) => $this->line('- ' . $file));
        }

        if ($this->option('delete')) {
            if (! $this->confirm('Yakin hapus permanen file tidak terpakai?')) {
                return self::SUCCESS;
            }

            $unusedFiles->each(fn($file) => $disk->delete($file));
            $this->info('File tidak terpakai berhasil dihapus permanen.');

            return self::SUCCESS;
        }

        if ($this->option('move')) {
            foreach ($unusedFiles as $file) {
                $target = '_unused_images/' . $file;

                $targetDir = dirname($disk->path($target));
                if (! File::exists($targetDir)) {
                    File::makeDirectory($targetDir, 0755, true);
                }

                if ($disk->exists($file)) {
                    $disk->move($file, $target);
                }
            }

            $this->info('File tidak terpakai berhasil dipindahkan ke storage/app/public/_unused_images.');

            return self::SUCCESS;
        }

        $this->newLine();
        $this->comment('Mode aman: belum ada file yang dihapus/dipindahkan.');
        $this->comment('Pakai --move untuk pindah ke quarantine, atau --delete untuk hapus permanen.');

        return self::SUCCESS;
    }

    private function collectUsedImagePaths()
    {
        $used = collect();

        $databaseName = DB::getDatabaseName();
        $tables = collect(DB::select('SHOW TABLES'))
            ->map(fn($row) => array_values((array) $row)[0]);

        foreach ($tables as $table) {
            $columns = collect(DB::select("SHOW COLUMNS FROM `{$table}`"))
                ->filter(function ($column) {
                    $type = strtolower($column->Type);

                    return str_contains($type, 'varchar')
                        || str_contains($type, 'text')
                        || str_contains($type, 'char');
                })
                ->pluck('Field');

            foreach ($columns as $column) {
                try {
                    DB::table($table)
                        ->whereNotNull($column)
                        ->select($column)
                        ->orderBy($column)
                        ->chunk(500, function ($rows) use (&$used, $column) {
                            foreach ($rows as $row) {
                                $value = (string) $row->{$column};

                                if (! preg_match('/\.(jpg|jpeg|png|webp)$/i', $value)) {
                                    continue;
                                }

                                $path = $this->normalizeStoragePath($value);

                                if ($path) {
                                    $used->push($path);
                                }
                            }
                        });
                } catch (\Throwable $e) {
                    // Skip column/table kalau ada query yang tidak cocok.
                }
            }
        }

        return $used->unique()->values();
    }

    private function normalizeStoragePath(string $value): ?string
    {
        $value = trim($value);

        $value = str_replace('\\', '/', $value);

        $value = preg_replace('#^https?://[^/]+/storage/#', '', $value);
        $value = preg_replace('#^/storage/#', '', $value);
        $value = preg_replace('#^storage/#', '', $value);
        $value = preg_replace('#^public/#', '', $value);

        if (! preg_match('/\.(jpg|jpeg|png|webp)$/i', $value)) {
            return null;
        }

        return ltrim($value, '/');
    }
}
