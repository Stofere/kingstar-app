<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePembelianRequest extends FormRequest
{
    public function authorize()
    {
        return true; // Izinkan admin
    }

    public function rules()
    {
        return [
            // Header Pembelian
            'id_supplier' => 'required|exists:supplier,id',
            'tanggal_pembelian' => 'required|date',
            'nomor_pembelian' => 'nullable|string|max:100|unique:pembelian,nomor_pembelian',
            'nomor_faktur_supplier' => 'nullable|string|max:100',
            'status_pembelian' => ['required', Rule::in(['DRAFT', 'DIPESAN', 'PENGIRIMAN', 'TIBA_SEBAGIAN', 'SELESAI', 'DIBATALKAN'])],
            'catatan' => 'nullable|string',
            // Validasi untuk Detail (Array)
            'details' => 'required|array|min:1', // Pastikan ada minimal 1 item detail
            'details.*.id_produk' => 'required|exists:produk,id',
            'details.*.jumlah' => 'required|integer|min:1',
            'details.*.harga_beli' => 'required|numeric|min:0',
            'details.*.catatan_item' => 'nullable|string', // Nama field unik untuk catatan per item
        ];
    }

    public function attributes()
    {
        return [
            'id_supplier' => 'Supplier',
            'tanggal_pembelian' => 'Tanggal Pembelian',
            'nomor_pembelian' => 'Nomor Pembelian Internal',
            'nomor_faktur_supplier' => 'Nomor Faktur Supplier',
            'status_pembelian' => 'Status Pembelian',
            'details' => 'Item Pembelian',
            'details.*.id_produk' => 'Produk',
            'details.*.jumlah' => 'Jumlah',
            'details.*.harga_beli' => 'Harga Beli Satuan',
            'details.*.catatan_item' => 'Catatan Item',
        ];
    }
     public function messages()
    {
        return [
            'details.required' => 'Minimal harus ada 1 item produk dalam pembelian.',
            'details.min' => 'Minimal harus ada 1 item produk dalam pembelian.',
        ];
    }
}