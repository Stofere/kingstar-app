<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePenjualanRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && in_array(auth()->user()->role, ['KASIR', 'ADMIN']);
    }

    public function rules()
    {
        return [
            'tanggal_penjualan' => 'required|date',
            'id_pelanggan' => 'nullable|exists:pelanggan,id',
            'pelanggan_baru_nama' => 'nullable|string|max:255',
            'pelanggan_baru_telepon' => 'nullable|string|max:20',
            'pelanggan_baru_alamat' => 'nullable|string',
            'total_harga' => 'required|numeric|min:0',
            'metode_pembayaran' => 'required|string',
            'kanal_transaksi' => 'required|string',
            'tipe_transaksi' => ['required', Rule::in(['BIASA', 'PESAN_BARANG'])],
            'uang_muka' => 'required_if:tipe_transaksi,PESAN_BARANG|nullable|numeric|min:0',
            'estimasi_kirim_at' => 'required_if:tipe_transaksi,PESAN_BARANG|nullable|date',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.id_produk' => 'required|exists:produk,id',
            'items.*.jumlah' => 'required|integer|min:1',
            'items.*.harga_jual' => 'required|numeric|min:0',
            'items.*.stok_allocations' => 'required_if:tipe_transaksi,BIASA|json',
        ];
    }
}