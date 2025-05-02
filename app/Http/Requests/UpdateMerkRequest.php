<?php 

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMerkRequest extends FormRequest {
    public function authorize() { return true; }
    public function rules() {
        $merkId = $this->route('merk')->id;
        return [
            'nama' => ['required', 'string', 'max:100', Rule::unique('merk')->ignore($merkId)],
        ];
    }
}