<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateJobOfferRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        $id = $this->route('offer')?->id;

        return [
            'title' => ['required','string','max:255'],
            'slug' => ['nullable','string','max:255',"unique:job_offers,slug,$id"],
            'company' => ['nullable','string','max:255'],
            'location' => ['nullable','string','max:255'],
            'contract_type' => ['nullable','string','max:255'],
            'sector' => ['nullable','string','max:255'],
            'excerpt' => ['nullable','string','max:5000'],
            'description' => ['nullable','string'],
            'missions' => ['nullable','string'],
            'requirements' => ['nullable','string'],
            'is_active' => ['nullable','boolean'],
            'published_at' => ['nullable','date'],
        ];
    }
}
