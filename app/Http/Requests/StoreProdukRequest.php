<?php 

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProdukRequest extends FormRequest {
    public function authorize() { return true; }
    public function rules() {
        return [
            'id_merk' => 'nullable|exists:merk,id', // Pastikan merk ada jika diisi
            'kode_produk' => 'nullable|string|max:100|unique:produk,kode_produk',
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'harga_jual_standart' => 'nullable|numeric|min:0',
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // Validasi gambar
            'satuan' => 'required|string|max:50',
            'memiliki_serial' => 'required|boolean',
            'durasi_garansi_standar_bulan' => 'nullable|integer|min:0',
            'status' => 'required|boolean',
        ];
    }
}