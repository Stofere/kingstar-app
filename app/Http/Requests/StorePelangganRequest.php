<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;

class StorePelangganRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Hanya admin yang bisa mengakses
     * @return bool
     */
    public function authorize()
    {
        return Auth::check() && Auth::user()->role == 'ADMIN';
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'nama' => ['required', 'string', 'max:255', 'unique:pelanggan,nama'], 
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
