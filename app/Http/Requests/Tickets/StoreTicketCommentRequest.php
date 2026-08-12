<?php

namespace App\Http\Requests\Tickets;

use Illuminate\Foundation\Http\FormRequest;

class StoreTicketCommentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'body' => [
                'required',
                'string',
                'max:5000',
                function ($attribute, $value, $fail) {
                    if (trim($value) === '') {
                        $fail('El seguimiento no puede estar vacío.');
                    }
                },
            ],
        ];
    }
}
