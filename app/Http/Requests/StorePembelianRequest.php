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
        // Hanya admin yang boleh akses form ini
        return Auth::check() && Auth::user()->role === 'ADMIN';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [
            'id_supplier' => 'required|exists:supplier,id',
            'tanggal_pembelian' => 'required|date',
            // Nomor pembelian: nullable, tapi jika diisi (oleh admin), harus unik dan sesuai format
            'nomor_pembelian' => [
                'nullable', // Boleh kosong (akan digenerate)
                'string',
                'max:100',
                Rule::unique('pembelian', 'nomor_pembelian'), // Pastikan unik jika diisi
                // Contoh Regex (sesuaikan jika perlu): PO-XXX-ddmmyy-ddd
                'regex:/^PO-[A-Z]{3}-\d{6}-\d{3}$/'
            ],
            'nomor_faktur_supplier' => 'nullable|string|max:100',
            'status_pembelian' => ['required', Rule::in(['DRAFT', 'DIPESAN'])],
            'status_pembayaran' => ['required', Rule::in(['BELUM_LUNAS', 'LUNAS', 'JATUH_TEMPO'])],
            'dibayar_at' => 'nullable|required_if:status_pembayaran,LUNAS|date', // Wajib jika lunas
            'catatan' => 'nullable|string',
            'status' => 'nullable|boolean', // Jika ada field status di form
            // Validasi untuk detail items
            'details' => 'required|array|min:1', // Harus ada minimal 1 item
            'details.*.id_produk' => 'required|exists:produk,id',
            'details.*.jumlah' => 'required|integer|min:1',
            'details.*.harga_beli' => 'required|numeric|min:0',
            // 'details.*.catatan' => 'nullable|string',
        ];

        // Jika user bukan admin, nomor pembelian tidak boleh diisi
        if (Auth::user()->role !== 'ADMIN') {
             // Mencegah user non-admin mengirimkan nomor pembelian
             $rules['nomor_pembelian'] = 'prohibited';
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
             'nomor_pembelian.regex' => 'Format nomor pembelian tidak sesuai (Contoh: PO-SBY-050524-001).',
             'nomor_pembelian.prohibited' => 'Anda tidak diizinkan mengisi nomor pembelian manual.',
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