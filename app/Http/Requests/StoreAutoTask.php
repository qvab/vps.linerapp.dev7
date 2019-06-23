<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAutoTask extends FormRequest
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
        return [
            'pipeline' => 'required|string',
            'task_type' => 'required|integer',
            'body' => 'required|string',
            'subdomain' => 'required|string',
            'statuses' => 'required|array',
            'responsible' => 'required|array',
            'schedule' => 'array',
        ];
    }
}

