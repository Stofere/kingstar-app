<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth; 
use Illuminate\Validation\Rule;

class UpdatePelangganRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Hanya Admin yang bisa mengupdate pelanggan.
     *
     * @return bool
     */
    public function authorize()
    {
        // Pastikan user yang login memiliki role ADMIN
        return Auth::check() && Auth::user()->role === 'ADMIN';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        // Ambil ID pelanggan dari route parameter
        $pelangganId = $this->route('pelanggan');

        return [
            'nama' => [
                'required',
                'string',
                'max:255',
                // Nama harus unik, tapi abaikan record pelanggan yang sedang diupdate
                Rule::unique('pelanggan', 'nama')->ignore($pelangganId),
            ],
            'telepon' => ['nullable', 'string', 'max:20'],
            'alamat' => ['nullable', 'string'],
            'status' => ['required', 'boolean'],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'nama.required' => 'Nama pelanggan wajib diisi.',
            'nama.unique' => 'Nama pelanggan sudah ada.',
            'nama.max' => 'Nama pelanggan maksimal 255 karakter.',
            'telepon.max' => 'Nomor telepon maksimal 20 karakter.',
            'status.required' => 'Status pelanggan wajib dipilih.',
            'status.boolean' => 'Status pelanggan tidak valid.',
        ];
    }
}