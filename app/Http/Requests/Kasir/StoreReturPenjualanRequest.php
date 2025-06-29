<?php

namespace App\Http\Requests\Kasir;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\DetailPenjualan;
use Illuminate\Validation\Rule;

class StoreReturPenjualanRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->check() && in_array(auth()->user()->role, ['KASIR', 'ADMIN']);
    }

    public function rules()
    {
        $rules = [
            'items_retur' => 'required|array|min:1',
            'tanggal_retur' => 'required|date_format:Y-m-d\TH:i',
            'catatan_global_retur' => 'nullable|string|max:1000',
        ];

        // Hitung total retur per item dari request saat ini
        $requestedReturQty = [];
        foreach ($this->input('items_retur', []) as $item) {
            $id = $item['id_detail_penjualan'];
            $qty = (int)($item['jumlah_retur'] ?? 0);
            $requestedReturQty[$id] = ($requestedReturQty[$id] ?? 0) + $qty;
        }

        foreach ($this->input('items_retur', []) as $key => $item) {
            $rules["items_retur.{$key}.id_detail_penjualan"] = ['required', 'integer'];

            $jumlahReturSaatIni = (int)($item['jumlah_retur'] ?? 0);
            $rules["items_retur.{$key}.jumlah_retur"] = 'required|integer|min:0';

            // Validasi kondisional untuk alasan dan tindakan
            if ($jumlahReturSaatIni > 0) {
                $rules["items_retur.{$key}.alasan_retur"] = 'required|string|max:255';
                $rules["items_retur.{$key}.tindakan_lanjut"] = 'required|string|max:100';
            }

            // Validasi kuantitas terhadap database + request saat ini
            $detailPenjualan = DetailPenjualan::with('returPenjualan')->find($item['id_detail_penjualan']);
            if ($detailPenjualan) {
                $totalSudahDireturDB = $detailPenjualan->returPenjualan->sum('jumlah_retur');
                $totalBisaDiretur = $detailPenjualan->jumlah - $totalSudahDireturDB;
                $rules["items_retur.{$key}.jumlah_retur"] .= '|max:' . $totalBisaDiretur;
            }
        }
        return $rules;
    }

    public function messages()
    {
        return [
            'items_retur.*.jumlah_retur.max' => 'Jumlah retur melebihi sisa yang bisa diretur dari database.',
            'items_retur.*.alasan_retur.required' => 'Alasan retur wajib diisi untuk item yang diretur.',
            'items_retur.*.tindakan_lanjut.required' => 'Tindakan lanjut wajib diisi untuk item yang diretur.',
        ];
    }
}