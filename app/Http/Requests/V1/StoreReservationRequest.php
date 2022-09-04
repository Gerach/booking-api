<?php

namespace App\Http\Requests\V1;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreReservationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        /** @var User|null $user */
        $user = $this->user();
        return $user && $user->tokenCan('create');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'reservedSince' => ['required', 'date', 'before_or_equal:reservedTill', 'after_or_equal:today'],
            'reservedTill' => ['required', 'date', 'after_or_equal:today'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reserved_since' => $this->reservedSince,
            'reserved_till' => $this->reservedTill,
        ]);
    }
}
