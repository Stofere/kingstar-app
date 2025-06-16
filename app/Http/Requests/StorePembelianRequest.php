<?php

namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth; // Import Auth
use Illuminate\Validation\Rule; // Import Rule

class StorePembelianRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        // Izinkan semua pengguna yang terotentikasi dan memiliki role yang sesuai
        return Auth::check() && Auth::user()->role === 'ADMIN';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $isPoRetur = $this->has('id_retur_asal');

        // =========================================================
        // ## ATURAN VALIDASI DINAMIS BERDASARKAN TIPE PO         ##
        // =========================================================
        $rules = [
            'id_supplier' => 'required|exists:supplier,id',
            'tanggal_pembelian' => 'required|date',
            'nomor_faktur_supplier' => 'nullable|string|max:100',
            'catatan' => 'nullable|string|max:1000',
            
            // Validasi untuk detail items
            'details' => 'required|array|min:1', // Harus ada minimal 1 item
            'details.*.id_produk' => 'required|exists:produk,id',
            'details.*.jumlah' => 'required|integer|min:1',
            'details.*.harga_beli' => 'required|numeric|min:0',
        ];

        if ($isPoRetur) {
            // --- Aturan Khusus untuk PO Barang Pengganti Retur ---
            $rules['id_retur_asal'] = 'required|integer|exists:retur_pembelian,id';
            // Nomor pembelian untuk PO Retur akan digenerate oleh controller,
            // namun validasi ini memastikan formatnya benar jika dikirim (misal dari field readonly yang diisi JS)
            // dan unik.
            $rules['nomor_pembelian'] = 'required|string|starts_with:PO-RTR-|unique:pembelian,nomor_pembelian';
            $rules['status_pembelian'] = ['required', Rule::in(['BARANG_PENGGANTI_RETUR'])];
            // Untuk PO retur, status pembayaran dan metode tidak wajib karena harga 0
            $rules['status_pembayaran'] = 'nullable|string'; // Seharusnya LUNAS by default dari controller
            $rules['metode_pembayaran'] = 'nullable|string';
            $rules['dibayar_at'] = 'nullable|date'; // Seharusnya tanggal PO by default dari controller

        } else {
            // --- Aturan untuk PO Biasa ---
            // Nomor pembelian akan digenerate oleh controller,
            // validasi ini untuk memastikan format dan keunikan jika dikirim dari form (readonly yang diisi JS)
            $rules['nomor_pembelian'] = [
                'required', // Karena form create.blade.php selalu mengirimkannya (diisi oleh JS)
                'string',
                'max:100',
                Rule::unique('pembelian', 'nomor_pembelian'),
                'regex:/^PO-[A-Z]{3}-\d{6}-\d{3}$/' // Regex PO-XXX-ddmmyy-001
            ];
            $rules['status_pembelian'] = ['required', Rule::in(['DRAFT', 'DIPESAN'])];
            $rules['status_pembayaran'] = ['required', Rule::in(['BELUM_LUNAS', 'LUNAS', 'JATUH_TEMPO'])];
            $rules['metode_pembayaran'] = 'nullable|string|max:50';
            $rules['dibayar_at'] = 'nullable|date|required_if:status_pembayaran,LUNAS';
        }


        return $rules;
    }

     /**
      * Get custom messages for validator errors.
      *
      * @return array
      */
     public function messages()
     {
         return [
            'id_supplier.required' => 'Supplier wajib dipilih.',
             'details.required' => 'Minimal harus ada 1 item produk dalam pembelian.',
             'details.min' => 'Minimal harus ada 1 item produk dalam pembelian.',
             'details.*.id_produk.required' => 'Produk pada baris :attribute harus dipilih.',
             'details.*.id_produk.exists' => 'Produk yang dipilih pada baris :attribute tidak valid.',
             'details.*.jumlah.required' => 'Jumlah pada baris :attribute harus diisi.',
             'details.*.jumlah.integer' => 'Jumlah pada baris :attribute harus berupa angka.',
             'details.*.jumlah.min' => 'Jumlah pada baris :attribute minimal 1.',
             'details.*.harga_beli.required' => 'Harga beli pada baris :attribute harus diisi.',
             'details.*.harga_beli.numeric' => 'Harga beli pada baris :attribute harus berupa angka.',
             'details.*.harga_beli.min' => 'Harga beli pada baris :attribute minimal 0.',
            'nomor_pembelian.unique' => 'Nomor pembelian ini sudah digunakan.',
            'nomor_pembelian.regex' => 'Format nomor pembelian tidak sesuai (Contoh: PO-SBY-010824-001).',
            'dibayar_at.required_if' => 'Tanggal bayar wajib diisi jika status pembayaran adalah LUNAS.',
         ];
     }

      /**
       * Get custom attributes for validator errors.
       *
       * @return array
       */
     public function attributes()
     {
         $attributes = [];
         if ($this->input('details')) {
             foreach($this->input('details') as $key => $val) {
                 $attributes["details.{$key}.id_produk"] = "produk (baris ".($key+1).")";
                 $attributes["details.{$key}.jumlah"] = "jumlah (baris ".($key+1).")";
                 $attributes["details.{$key}.harga_beli"] = "harga beli (baris ".($key+1).")";
             }
         }
         return $attributes;
     }
}