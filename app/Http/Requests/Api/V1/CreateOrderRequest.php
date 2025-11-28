<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

class CreateOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'address_id' => 'required|exists:addresses,id',
            'type' => 'required|in:delivery,pickup',
            'payment_method' => 'required|in:card,mobile_money,cash',
            'promo_code' => 'nullable|string|exists:promo_codes,code',
            'special_instructions' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'address_id.required' => 'L\'adresse de livraison est obligatoire.',
            'address_id.exists' => 'L\'adresse sélectionnée n\'existe pas.',
            'type.required' => 'Le type de commande est obligatoire.',
            'type.in' => 'Le type doit être : delivery ou pickup.',
            'payment_method.required' => 'La méthode de paiement est obligatoire.',
            'payment_method.in' => 'La méthode de paiement doit être : card, mobile_money ou cash.',
            'promo_code.exists' => 'Le code promo n\'existe pas ou n\'est plus valide.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     */
    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors()
            ], 422)
        );
    }
}
