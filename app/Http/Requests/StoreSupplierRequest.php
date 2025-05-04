<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true; // Izinkan Admin
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'nama' => 'required|string|max:255',
            // Telepon wajib dan unik di tabel supplier
            'telepon' => 'required|string|max:20|unique:supplier,telepon',
            // Email tidak wajib, tapi jika diisi harus format email dan unik
            'email' => 'nullable|email|max:255|unique:supplier,email',
            'alamat' => 'nullable|string',
            // Status wajib dan harus boolean (0 atau 1)
            'status' => 'required|in:0,1',
        ];
    }

     /**
     * Atribut kustom untuk pesan error.
     */
    public function attributes()
    {
        return [
            'nama' => 'Nama Supplier',
            'telepon' => 'Nomor Telepon',
            'email' => 'Alamat Email',
            'alamat' => 'Alamat Supplier',
            'status' => 'Status Supplier',
        ];
    }
}
