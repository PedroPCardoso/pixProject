<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransactionRequest extends FormRequest
{
    public function authorize()
    {
        return true;
    }

    public function rules()
    {
        return [
            'amount' => 'required|numeric',
            'timestamp' => [
                'required',
                'regex:/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/',
                'before_or_equal:now'
            ],
        ];
    }

    public function messages()
    {
        return [
            'amount.required' => 'The amount field is required.',
            'amount.numeric' => 'The amount must be a number.',
            'timestamp.required' => 'The timestamp field is required.',
            'timestamp.date_format' => 'The timestamp must be a valid ISO 8601 date.',
            'timestamp.before_or_equal' => 'The timestamp must be a date before or equal to now.',
        ];
    }
}
