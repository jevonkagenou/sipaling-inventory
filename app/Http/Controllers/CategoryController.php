<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{
    /**
     * Menampilkan daftar seluruh kategori master.
     */
    public function index(Request $request): JsonResponse
    {
        $categories = Category::withCount('products')
            ->orderBy('name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $categories,
        ]);
    }

    /**
     * Menyimpan kategori baru ke database.
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:categories,name'],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 100 karakter.',
            'name.unique' => 'Nama kategori ini sudah terdaftar.',
            'description.max' => 'Deskripsi kategori maksimal 255 karakter.',
        ]);

        $category = DB::transaction(function () use ($validated, $request) {
            $baseSlug = Str::slug($validated['name']);
            $slug = $baseSlug;
            $counter = 1;
            while (Category::where('slug', $slug)->exists()) {
                $slug = "{$baseSlug}-{$counter}";
                $counter++;
            }

            $created = Category::create([
                'name' => $validated['name'],
                'slug' => $slug,
                'description' => $validated['description'] ?? null,
            ]);

            ActivityLog::create([
                'log_name' => 'inventory',
                'description' => "Menambahkan kategori baru: {$created->name}",
                'subject_type' => Category::class,
                'subject_id' => $created->id,
                'causer_type' => Auth::user() ? get_class(Auth::user()) : null,
                'causer_id' => Auth::id(),
                'event' => 'created',
                'properties' => [
                    'attributes' => $created->toArray(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);

            return $created;
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Kategori berhasil ditambahkan.',
                'category' => $category,
            ], 201);
        }

        return redirect()->back()->with('success', "Kategori '{$category->name}' berhasil ditambahkan.");
    }

    /**
     * Memperbarui informasi kategori yang sudah ada.
     */
    public function update(Request $request, Category $category): RedirectResponse|JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('categories', 'name')->ignore($category->id)],
            'description' => ['nullable', 'string', 'max:255'],
        ], [
            'name.required' => 'Nama kategori wajib diisi.',
            'name.max' => 'Nama kategori maksimal 100 karakter.',
            'name.unique' => 'Nama kategori ini sudah digunakan oleh kategori lain.',
            'description.max' => 'Deskripsi kategori maksimal 255 karakter.',
        ]);

        DB::transaction(function () use ($validated, $request, $category) {
            $oldAttributes = $category->only(['name', 'slug', 'description']);

            $updateData = [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
            ];

            if ($category->name !== $validated['name']) {
                $baseSlug = Str::slug($validated['name']);
                $slug = $baseSlug;
                $counter = 1;
                while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                    $slug = "{$baseSlug}-{$counter}";
                    $counter++;
                }
                $updateData['slug'] = $slug;
            }

            $category->update($updateData);

            ActivityLog::create([
                'log_name' => 'inventory',
                'description' => "Memperbarui kategori: {$category->name}",
                'subject_type' => Category::class,
                'subject_id' => $category->id,
                'causer_type' => Auth::user() ? get_class(Auth::user()) : null,
                'causer_id' => Auth::id(),
                'event' => 'updated',
                'properties' => [
                    'old' => $oldAttributes,
                    'attributes' => $category->getChanges(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => 'Kategori berhasil diperbarui.',
                'category' => $category->fresh(),
            ]);
        }

        return redirect()->back()->with('success', "Kategori '{$category->name}' berhasil diperbarui.");
    }

    /**
     * Menghapus kategori dari database dengan proteksi restrict jika ada produk terikat.
     */
    public function destroy(Request $request, Category $category): RedirectResponse|JsonResponse
    {
        $productsCount = $category->products()->count();

        if ($productsCount > 0) {
            $errorMessage = "Kategori '{$category->name}' tidak dapat dihapus karena masih digunakan oleh {$productsCount} produk.";

            if ($request->wantsJson()) {
                return response()->json([
                    'message' => $errorMessage,
                ], 422);
            }

            return redirect()->back()->withErrors(['category_error' => $errorMessage])->with('error', $errorMessage);
        }

        DB::transaction(function () use ($category, $request) {
            ActivityLog::create([
                'log_name' => 'inventory',
                'description' => "Menghapus kategori: {$category->name}",
                'subject_type' => Category::class,
                'subject_id' => $category->id,
                'causer_type' => Auth::user() ? get_class(Auth::user()) : null,
                'causer_id' => Auth::id(),
                'event' => 'deleted',
                'properties' => [
                    'attributes' => $category->toArray(),
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                ],
            ]);

            $category->delete();
        });

        if ($request->wantsJson()) {
            return response()->json([
                'message' => "Kategori '{$category->name}' berhasil dihapus.",
            ]);
        }

        return redirect()->back()->with('success', "Kategori '{$category->name}' berhasil dihapus.");
    }
}
