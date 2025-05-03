<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProdukRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Izinkan request jika pengguna adalah admin (atau sesuai logic otorisasi Anda)
        return true; // Atau Auth::user()->role == 'ADMIN';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            // id_merk boleh null, tapi jika diisi, harus ada di tabel merk
            'id_merk' => 'nullable|exists:merk,id',
            // kode_produk boleh null, tapi jika diisi, harus unik di tabel produk
            'kode_produk' => 'nullable|string|max:100|unique:produk,kode_produk',
            // nama wajib diisi
            'nama' => 'required|string|max:255',
            'deskripsi' => 'nullable|string',
            // harga jual boleh null, tapi jika diisi harus angka >= 0
            'harga_jual_standart' => 'nullable|numeric|min:0',
            // gambar boleh null, tapi jika diisi harus berupa image dengan tipe & ukuran tertentu
            'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // Maks 2MB
            // satuan wajib diisi
            'satuan' => 'required|string|max:50',
            // memiliki_serial wajib diisi dan harus boolean (tervalidasi dari input '1'/'0')
            'memiliki_serial' => 'required|in:0,1',
            // durasi garansi boleh null, tapi jika diisi harus integer >= 0
            'durasi_garansi_standar_bulan' => 'nullable|integer|min:0',
            // status wajib diisi dan harus boolean (tervalidasi dari input '1'/'0')
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