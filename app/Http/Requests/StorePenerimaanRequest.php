<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Produk;
use App\Models\DetailPembelian;
use Illuminate\Validation\Rule;

class StorePenerimaanRequest extends FormRequest
{
    public function authorize()
    {
        // Pastikan user memiliki role GUDANG atau ADMIN
        return auth()->check() && in_array(auth()->user()->role, ['GUDANG', 'ADMIN']);
    }

    public function rules()
    {
        $rules = [
            'tipe_penerimaan' => ['required', Rule::in(['PO', 'MANUAL'])],
            'id_pembelian' => [
                Rule::requiredIf(fn () => $this->input('tipe_penerimaan') === 'PO'),
                'nullable',
                'exists:pembelian,id'
            ],
            'id_supplier_manual' => [ // Opsional untuk manual
                // Rule::requiredIf(fn () => $this->input('tipe_penerimaan') === 'MANUAL' && $this->filled('id_supplier_manual')), // Hapus jika benar-benar opsional
                'nullable',
                'exists:supplier,id'
            ],
            'diterima_at' => ['required', 'date'],
            'no_surat_jalan' => ['nullable', 'string', 'max:100'],
            'items' => ['required', 'array', 'min:1'], // Minimal 1 item di-submit
        ];

        $totalItemsDiterima = 0;

        foreach ($this->input('items', []) as $key => $item) {
            $itemIndexForMessage = $key + 1; // Untuk pesan error yang lebih user-friendly
            $jumlahDiterimaSekarang = (int)($item['jumlah_diterima_sekarang'] ?? 0);
            $totalItemsDiterima += $jumlahDiterimaSekarang;

            $rules["items.{$key}.id_produk"] = ['required', 'exists:produk,id'];
            $rules["items.{$key}.jumlah_diterima_sekarang"] = ['required', 'integer', 'min:0']; // Boleh 0, tapi akan di-skip di controller
            $rules["items.{$key}.lokasi"] = ['required', Rule::in(['GUDANG', 'TOKO'])];
            $rules["items.{$key}.kondisi"] = ['required', Rule::in(['BAIK', 'RUSAK_MINOR', 'RUSAK', 'SERVIS', 'KOMPLAIN_SUPPLIER'])];
            $rules["items.{$key}.tipe_garansi"] = ['required', Rule::in(['NONE', 'RESMI', 'SELF_SERVICE'])];

            // Validasi jumlah diterima sekarang vs sisa PO
            if ($this->input('tipe_penerimaan') === 'PO' && isset($item['id_detail_pembelian'])) {
                $rules["items.{$key}.id_detail_pembelian"] = ['required', 'exists:detail_pembelian,id'];
                $detailPo = DetailPembelian::find($item['id_detail_pembelian']);
                if ($detailPo) {
                    $sisaBelumDiterima = $detailPo->jumlah - $detailPo->jumlah_diterima;
                    // Hanya validasi max jika jumlah diterima > 0
                    if ($jumlahDiterimaSekarang > 0) {
                        $rules["items.{$key}.jumlah_diterima_sekarang"][] = 'max:' . $sisaBelumDiterima;
                    }
                }
            } elseif ($this->input('tipe_penerimaan') === 'MANUAL') {
                 // Untuk manual, jumlah diterima harus > 0 jika baris itu dipertimbangkan
                 if ($jumlahDiterimaSekarang <=0 && count($this->input('items')) == 1) { // Jika hanya 1 item dan qty 0
                    // Ini akan ditangani dengan validasi $totalItemsDiterima di after()
                 } elseif ($jumlahDiterimaSekarang <=0 && count($this->input('items')) > 1) {
                    // Jika ada item lain, item ini bisa diabaikan jika qty 0
                 } else {
                    $rules["items.{$key}.jumlah_diterima_sekarang"][] = 'min:1';
                 }
            }


            // Validasi nomor seri
            $produk = Produk::find($item['id_produk'] ?? null);
            if ($produk && $produk->memiliki_serial) {
                if ($jumlahDiterimaSekarang > 0) {
                    $rules["items.{$key}.nomor_seri"] = ['required', 'array', "size:{$jumlahDiterimaSekarang}"];
                    $rules["items.{$key}.nomor_seri.*"] = [
                        'required',
                        'string',
                        'distinct:ignore_case', // Tidak case sensitive untuk duplikasi dalam request
                        'max:255',
                        // Validasi unique global di LogNomorSeri akan dilakukan di controller untuk pesan error yang lebih baik
                        // Rule::unique('log_nomor_seri', 'nomor_seri')->where(function ($query) use ($produk) {
                        //    return $query->where('id_produk', $produk->id)->whereNotIn('status_log', ['DIRETUR_SUPPLIER', 'HILANG']);
                        // })->ignore(null, 'id_log_nomor_seri') // Ganti 'id_log_nomor_seri' jika nama kolom PK berbeda
                    ];
                } elseif ($jumlahDiterimaSekarang === 0) {
                    // Jika jumlah 0, nomor seri tidak boleh ada
                    $rules["items.{$key}.nomor_seri"] = ['nullable', 'array', 'max:0'];
                }
            }
        }
        // Pastikan minimal ada satu item yang benar-benar diterima
        if ($totalItemsDiterima <= 0 && count($this->input('items', [])) > 0) {
            $rules['items_diterima_check'] = ['required']; // Aturan dummy untuk memicu pesan error
        }

        return $rules;
    }

    public function messages()
    {
        $messages = [
            'items_diterima_check.required' => 'Minimal ada satu item yang diterima dengan jumlah lebih dari 0.',
        ];
        foreach ($this->input('items', []) as $key => $item) {
            $itemNumber = $key + 1;
            $messages["items.{$key}.id_produk.required"] = "Produk wajib dipilih untuk item ke-{$itemNumber}.";
            $messages["items.{$key}.id_produk.exists"] = "Produk yang dipilih untuk item ke-{$itemNumber} tidak valid.";
            $messages["items.{$key}.jumlah_diterima_sekarang.required"] = "Jumlah diterima sekarang wajib diisi untuk item ke-{$itemNumber}.";
            $messages["items.{$key}.jumlah_diterima_sekarang.integer"] = "Jumlah diterima sekarang untuk item ke-{$itemNumber} harus angka.";
            $messages["items.{$key}.jumlah_diterima_sekarang.min"] = "Jumlah diterima sekarang untuk item ke-{$itemNumber} minimal 0 (atau 1 untuk manual).";
            $messages["items.{$key}.jumlah_diterima_sekarang.max"] = "Jumlah diterima untuk item ke-{$itemNumber} tidak boleh melebihi sisa dari PO.";
            $messages["items.{$key}.lokasi.required"] = "Lokasi wajib dipilih untuk item ke-{$itemNumber}.";
            $messages["items.{$key}.kondisi.required"] = "Kondisi wajib dipilih untuk item ke-{$itemNumber}.";
            $messages["items.{$key}.tipe_garansi.required"] = "Tipe garansi wajib dipilih untuk item ke-{$itemNumber}.";

            $messages["items.{$key}.nomor_seri.required"] = "Nomor seri wajib diisi untuk item ke-{$itemNumber} (sesuai jumlah diterima).";
            $messages["items.{$key}.nomor_seri.array"] = "Format nomor seri untuk item ke-{$itemNumber} tidak valid.";
            $messages["items.{$key}.nomor_seri.size"] = "Jumlah nomor seri untuk item ke-{$itemNumber} harus sesuai dengan jumlah diterima.";
            $messages["items.{$key}.nomor_seri.max"] = "Tidak boleh ada nomor seri untuk item ke-{$itemNumber} jika jumlah diterima adalah 0.";
            $messages["items.{$key}.nomor_seri.*.required"] = "Setiap nomor seri untuk item ke-{$itemNumber} wajib diisi.";
            $messages["items.{$key}.nomor_seri.*.string"] = "Setiap nomor seri untuk item ke-{$itemNumber} harus teks.";
            $messages["items.{$key}.nomor_seri.*.distinct"] = "Nomor seri untuk item ke-{$itemNumber} tidak boleh ada yang sama dalam satu input item.";
            $messages["items.{$key}.nomor_seri.*.max"] = "Setiap nomor seri untuk item ke-{$itemNumber} maksimal 255 karakter.";
            // $messages["items.{$key}.nomor_seri.*.unique"] = "Salah satu Nomor Seri untuk item ke-{$itemNumber} sudah ada di sistem.";
        }
        return $messages;
    }
}