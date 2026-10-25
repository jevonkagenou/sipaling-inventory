<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class ProductImportService
{
    /**
     * Mengimpor data produk massal dari berkas CSV secara atomik.
     *
     * @return array{imported_count: int, skipped_count: int, message: string}
     */
    public function import(UploadedFile $file, ?User $actor = null, array $meta = []): array
    {
        $filePath = $file->getRealPath();
        $handle = fopen($filePath, 'r');

        if (! $handle) {
            throw new InvalidArgumentException('Gagal membaca berkas CSV.');
        }

        // Baca baris pertama (header) dan bersihkan BOM jika ada
        $firstLine = fgets($handle);
        if (! $firstLine) {
            fclose($handle);
            throw new InvalidArgumentException('Berkas CSV kosong.');
        }

        $firstLine = str_replace("\xEF\xBB\xBF", '', $firstLine);
        $delimiter = strpos($firstLine, ';') !== false ? ';' : ',';
        $headerColumns = array_map(fn ($col) => strtolower(trim($col)), str_getcsv($firstLine, $delimiter));

        $colMap = array_flip($headerColumns);
        $requiredKeys = ['sku', 'name'];

        foreach ($requiredKeys as $reqKey) {
            if (! isset($colMap[$reqKey])) {
                fclose($handle);
                throw new InvalidArgumentException("Format CSV tidak valid. Kolom '{$reqKey}' tidak ditemukan pada baris judul.");
            }
        }

        $importedCount = 0;
        $skippedCount = 0;
        $existingSkus = Product::pluck('sku')->map(fn ($s) => strtoupper(trim($s)))->flip()->toArray();

        // Cache kategori untuk performa cepat
        $categoriesCache = Category::all()->keyBy(fn ($c) => strtolower(trim($c->name)));

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle, 4096, $delimiter)) !== false) {
                if (empty($row) || (count($row) === 1 && empty($row[0]))) {
                    continue;
                }

                $sku = isset($colMap['sku']) && isset($row[$colMap['sku']]) ? strtoupper(trim($row[$colMap['sku']])) : '';
                $name = isset($colMap['name']) && isset($row[$colMap['name']]) ? trim($row[$colMap['name']]) : '';

                if (empty($sku) || empty($name)) {
                    $skippedCount++;
                    continue;
                }

                // Cek duplikasi SKU
                if (isset($existingSkus[$sku])) {
                    $skippedCount++;
                    continue;
                }

                // Resolusi Kategori
                $catName = isset($colMap['category']) && isset($row[$colMap['category']]) ? trim($row[$colMap['category']]) : 'Umum';
                if (empty($catName)) {
                    $catName = 'Umum';
                }

                $catKey = strtolower($catName);
                if (! isset($categoriesCache[$catKey])) {
                    $baseSlug = Str::slug($catName) ?: 'kategori-'.Str::random(5);
                    $slug = $baseSlug;
                    $counter = 1;
                    while (Category::where('slug', $slug)->exists()) {
                        $slug = "{$baseSlug}-{$counter}";
                        $counter++;
                    }

                    $newCat = Category::create([
                        'name' => $catName,
                        'slug' => $slug,
                        'description' => 'Kategori hasil impor CSV',
                    ]);
                    $categoriesCache[$catKey] = $newCat;
                }
                $category = $categoriesCache[$catKey];

                $unit = isset($colMap['unit']) && isset($row[$colMap['unit']]) && ! empty(trim($row[$colMap['unit']]))
                    ? trim($row[$colMap['unit']])
                    : 'unit';

                $unitPrice = isset($colMap['unit_price']) && isset($row[$colMap['unit_price']])
                    ? max(0, (float) preg_replace('/[^0-9.]/', '', str_replace(',', '.', $row[$colMap['unit_price']])))
                    : 0;

                $currentStock = isset($colMap['current_stock']) && isset($row[$colMap['current_stock']])
                    ? max(0, (int) preg_replace('/[^0-9]/', '', $row[$colMap['current_stock']]))
                    : 0;

                $minimumStock = isset($colMap['minimum_stock']) && isset($row[$colMap['minimum_stock']])
                    ? max(0, (int) preg_replace('/[^0-9]/', '', $row[$colMap['minimum_stock']]))
                    : 5;

                $description = isset($colMap['description']) && isset($row[$colMap['description']])
                    ? trim($row[$colMap['description']])
                    : null;

                Product::create([
                    'sku' => $sku,
                    'name' => $name,
                    'category_id' => $category->id,
                    'unit' => $unit,
                    'unit_price' => $unitPrice,
                    'current_stock' => $currentStock,
                    'minimum_stock' => $minimumStock,
                    'description' => $description,
                ]);

                $existingSkus[$sku] = true;
                $importedCount++;
            }

            fclose($handle);

            $user = $actor ?? Auth::user();

            ActivityLog::create([
                'log_name' => 'inventory',
                'description' => "Mengimpor {$importedCount} produk baru dari berkas CSV ({$file->getClientOriginalName()})",
                'subject_type' => Product::class,
                'causer_type' => $user ? get_class($user) : null,
                'causer_id' => $user?->id,
                'event' => 'created',
                'properties' => [
                    'imported_count' => $importedCount,
                    'skipped_count' => $skippedCount,
                    'filename' => $file->getClientOriginalName(),
                    'ip' => $meta['ip'] ?? request()?->ip(),
                    'user_agent' => $meta['user_agent'] ?? request()?->userAgent(),
                ],
            ]);

            DB::commit();

            $message = "Proses impor selesai: {$importedCount} produk berhasil ditambahkan.";
            if ($skippedCount > 0) {
                $message .= " ({$skippedCount} baris dilewati karena duplikat atau format tidak lengkap).";
            }

            return [
                'imported_count' => $importedCount,
                'skipped_count' => $skippedCount,
                'message' => $message,
            ];
        } catch (\Throwable $e) {
            DB::rollBack();
            if (is_resource($handle)) {
                fclose($handle);
            }
            throw $e;
        }
    }
}
