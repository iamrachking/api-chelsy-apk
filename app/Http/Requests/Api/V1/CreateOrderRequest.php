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
        'address_id' => 'required_if:type,delivery|nullable|exists:addresses,id',
        'type' => 'required|in:delivery,pickup',
        'payment_method' => 'required|in:card,mobile_money,cash',
        'mobile_money_provider' => 'nullable|in:MTN,Moov,mtn,moov', // CHANGÉ: nullable au lieu de required_if
        'mobile_money_number' => [
            'nullable', // CHANGÉ: nullable au lieu de required_if
            'string',
            'regex:/^(\+229)?[0-9]{8,10}$/',
        ],
        'promo_code' => 'nullable|string|exists:promo_codes,code',
        'scheduled_at' => 'nullable|date',
        'special_instructions' => 'nullable|string|max:500',
    ];
}

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'address_id.required_if' => 'L\'adresse de livraison est obligatoire pour les commandes de type delivery.',
            'address_id.exists' => 'L\'adresse sélectionnée n\'existe pas.',
            'type.required' => 'Le type de commande est obligatoire.',
            'type.in' => 'Le type doit être : delivery ou pickup.',
            'payment_method.required' => 'La méthode de paiement est obligatoire.',
            'payment_method.in' => 'La méthode de paiement doit être : card, mobile_money ou cash.',
            'mobile_money_provider.required_if' => 'Le fournisseur Mobile Money est obligatoire pour les paiements Mobile Money.',
            'mobile_money_provider.in' => 'Le fournisseur doit être : MTN ou Moov.',
            'mobile_money_number.required_if' => 'Le numéro Mobile Money est obligatoire pour les paiements Mobile Money.',
            'mobile_money_number.regex' => 'Format de numéro invalide. Utilisez +229XXXXXXXX ou XXXXXXXX.',
            'promo_code.exists' => 'Le code promo n\'existe pas ou n\'est plus valide.',
            'scheduled_at.date' => 'La date de programmation doit être une date valide.',
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