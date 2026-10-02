<?php

namespace App\Http\Requests;

use App\Models\Product;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'sku' => [
                'required',
                'string',
                'max:50',
                Rule::unique(Product::class, 'sku'),
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'category_id' => [
                'required',
                'string',
                Rule::exists('categories', 'id'),
            ],
            'unit' => [
                'required',
                'string',
                'max:30',
            ],
            'unit_price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'current_stock' => [
                'required',
                'integer',
                'min:0',
            ],
            'minimum_stock' => [
                'required',
                'integer',
                'min:0',
            ],
            'description' => [
                'nullable',
                'string',
                'max:1000',
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'sku.required' => 'Kode SKU wajib diisi.',
            'sku.unique' => 'Kode SKU sudah terdaftar dalam sistem inventaris.',
            'sku.max' => 'Kode SKU tidak boleh lebih dari 50 karakter.',
            'name.required' => 'Nama barang wajib diisi.',
            'name.max' => 'Nama barang tidak boleh lebih dari 255 karakter.',
            'category_id.required' => 'Kategori barang wajib dipilih.',
            'category_id.exists' => 'Kategori barang yang dipilih tidak valid.',
            'unit.required' => 'Satuan barang wajib diisi.',
            'unit.max' => 'Satuan barang tidak boleh lebih dari 30 karakter.',
            'unit_price.required' => 'Harga satuan barang wajib diisi.',
            'unit_price.numeric' => 'Harga satuan harus berupa nilai numerik.',
            'unit_price.min' => 'Harga satuan tidak boleh kurang dari 0.',
            'current_stock.required' => 'Jumlah stok saat ini wajib diisi.',
            'current_stock.integer' => 'Jumlah stok saat ini harus berupa bilangan bulat.',
            'current_stock.min' => 'Jumlah stok saat ini tidak boleh bernilai negatif.',
            'minimum_stock.required' => 'Batas minimum stok wajib diisi.',
            'minimum_stock.integer' => 'Batas minimum stok harus berupa bilangan bulat.',
            'minimum_stock.min' => 'Batas minimum stok tidak boleh bernilai negatif.',
            'description.max' => 'Deskripsi barang tidak boleh lebih dari 1000 karakter.',
        ];
    }
}
