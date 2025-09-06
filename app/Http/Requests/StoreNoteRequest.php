<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;


class StoreNoteRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return Auth::check();
    }


    public function rules(): array
    {
        return [
            'title' => 'required|string|max:150',
            'body' => 'required|string',
            'tags' => 'sometimes|array|max:10',
            'tags.*' => 'string|max:40'
        ];
    }
}
