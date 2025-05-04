<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSupplierRequest extends FormRequest
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

    public function rules()
    {
        $supplierId = $this->route('supplier')->id; // Ambil ID dari route model binding

        return [
            'nama' => 'required|string|max:255',
            // Telepon wajib dan unik, abaikan supplier saat ini
            'telepon' => ['required', 'string', 'max:20', Rule::unique('supplier')->ignore($supplierId)],
            // Email tidak wajib, tapi jika diisi harus format email dan unik, abaikan supplier saat ini
            'email' => ['nullable', 'email', 'max:255', Rule::unique('supplier')->ignore($supplierId)],
            'alamat' => 'nullable|string',
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