<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule; // Import Rule

class UpdateMerkRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $merkId = $this->route('merk')->id; // Dapatkan ID merk dari route model binding
        return [
            // Nama wajib, unik, tapi abaikan merk saat ini
            'nama' => [
                'required',
                'string',
                'max:100',
                Rule::unique('merk')->ignore($merkId)
            ],
        ];
    }
}