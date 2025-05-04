<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePembelianRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        $pembelianId = $this->route('pembelian')->id;
        return [
            // Header Pembelian
            'id_supplier' => 'required|exists:supplier,id',
            'tanggal_pembelian' => 'required|date',
            'nomor_pembelian' => ['nullable', 'string', 'max:100', Rule::unique('pembelian')->ignore($pembelianId)],
            'nomor_faktur_supplier' => 'nullable|string|max:100',
            'status_pembelian' => ['required', Rule::in(['DRAFT', 'DIPESAN', 'PENGIRIMAN', 'TIBA_SEBAGIAN', 'SELESAI', 'DIBATALKAN'])],
            'catatan' => 'nullable|string',
            // Validasi untuk Detail (Array)
            'details' => 'required|array|min:1',
            'details.*.id_produk' => 'required|exists:produk,id',
            'details.*.jumlah' => 'required|integer|min:1',
            'details.*.harga_beli' => 'required|numeric|min:0',
            'details.*.catatan_item' => 'nullable|string',
             // Mungkin perlu validasi ID detail jika ingin memastikan detail lama tidak dihapus semua
            // 'details.*.detail_id' => 'nullable|exists:detail_pembelian,id',
        ];
    }
     public function attributes()
    {
         // Sama seperti StorePembelianRequest
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
         // Sama seperti StorePembelianRequest
         return [
            'details.required' => 'Minimal harus ada 1 item produk dalam pembelian.',
            'details.min' => 'Minimal harus ada 1 item produk dalam pembelian.',
        ];
    }
}