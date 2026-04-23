<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreJobApplicationRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'job_offer_id' => ['nullable','exists:job_offers,id'],
            'full_name' => ['required','string','max:255'],
            'email' => ['required','email','max:255'],
            'phone' => ['nullable','string','max:255'],
            'city' => ['nullable','string','max:255'],
            'message' => ['nullable','string','max:10000'],

            'cv' => ['nullable','file','mimes:pdf,doc,docx','max:5120'],
            'letter' => ['nullable','file','mimes:pdf,doc,docx','max:5120'],
        ];
    }
}
