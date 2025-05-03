<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; // <-- Import Rule

class UpdateProdukRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Izinkan admin
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        // Dapatkan ID produk yang sedang diedit dari parameter route
        $produkId = $this->route('produk')->id;

        return [
            'id_merk' => 'nullable|exists:merk,id',
            // Kode produk boleh null, tapi jika diisi, harus unik KECUALI untuk produk ini sendiri
            'kode_produk' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('produk', 'kode_produk')->ignore($produkId) // Abaikan ID saat ini
            ],
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            'harga_jual_standart' => 'nullable|numeric|min:0',
            // Gambar tidak wajib saat update, tapi jika diisi harus valid
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'satuan' => 'required|string|max:50',
            'memiliki_serial' => 'required|in:0,1',
            'durasi_garansi_standar_bulan' => 'nullable|integer|min:0',
            'status' => 'required|in:0,1',
        ];
    }

     /**
     * Atur atribut kustom untuk pesan error (opsional).
     *
     * @return array
     */
    public function attributes()
    {
        // Sama seperti StoreProdukRequest
        return [
            'id_merk' => 'Merk',
            'kode_produk' => 'Kode Produk',
            'nama' => 'Nama Produk',
            'harga_jual_standart' => 'Harga Jual Standar',
            'memiliki_serial' => 'Memiliki Nomor Seri',
            'durasi_garansi_standar_bulan' => 'Durasi Garansi Standar',
            'status' => 'Status Produk',
        ];
    }
}