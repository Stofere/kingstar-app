<?php 

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProdukRequest extends FormRequest {
    public function authorize() { return true; }
    public function rules() {
        $produkId = $this->route('produk')->id;
        return [
            'id_merk' => 'nullable|exists:merk,id',
            'kode_produk' => ['nullable', 'string', 'max:100', Rule::unique('produk')->ignore($produkId)],
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'harga_jual_standart' => 'nullable|numeric|min:0',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // Gambar opsional saat update
            'satuan' => 'required|string|max:50',
            'memiliki_serial' => 'required|boolean',
            'durasi_garansi_standar_bulan' => 'nullable|integer|min:0',
            'status' => 'required|boolean',
        ];
    }
}