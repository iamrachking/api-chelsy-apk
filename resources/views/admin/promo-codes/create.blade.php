@extends('admin.layout')

@section('title', 'Créer un Code Promo')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
        <div class="mb-6">
            <a href="{{ route('admin.promo-codes') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Retour
            </a>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center mt-4">
                <i class="fas fa-plus-circle text-blue-600 mr-3"></i>
                Créer un Code Promo
            </h1>
        </div>

        <form action="{{ route('admin.promo-codes.store') }}" method="POST" class="space-y-6">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="code">
                        <i class="fas fa-ticket-alt text-blue-500 mr-2"></i>Code *
                    </label>
                    <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all uppercase" 
                           id="code" type="text" name="code" value="{{ old('code') }}" required placeholder="PROMO10">
                    @error('code') 
                        <p class="text-red-500 text-xs mt-1 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p> 
                    @enderror
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="name">
                        <i class="fas fa-tag text-blue-500 mr-2"></i>Nom *
                    </label>
                    <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                           id="name" type="text" name="name" value="{{ old('name') }}" required placeholder="Promotion spéciale">
                    @error('name') 
                        <p class="text-red-500 text-xs mt-1 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p> 
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="description">
                    <i class="fas fa-align-left text-blue-500 mr-2"></i>Description
                </label>
                <textarea class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                          id="description" name="description" rows="3" placeholder="Description de la promotion">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="type">
                        <i class="fas fa-percent text-blue-500 mr-2"></i>Type de réduction *
                    </label>
                    <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                            id="type" name="type" required>
                        <option value="percentage" {{ old('type') === 'percentage' ? 'selected' : '' }}>Pourcentage (%)</option>
                        <option value="fixed" {{ old('type') === 'fixed' ? 'selected' : '' }}>Montant fixe (FCFA)</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="value">
                        <i class="fas fa-dollar-sign text-blue-500 mr-2"></i>Valeur *
                    </label>
                    <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                           id="value" type="number" step="0.01" name="value" value="{{ old('value') }}" required placeholder="10 ou 5000">
                    <p class="text-xs text-gray-500 mt-1">Pourcentage (ex: 10) ou montant fixe (ex: 5000)</p>
                    @error('value') 
                        <p class="text-red-500 text-xs mt-1 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p> 
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="minimum_order_amount">
                    <i class="fas fa-shopping-cart text-blue-500 mr-2"></i>Commande minimum (FCFA)
                </label>
                <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                       id="minimum_order_amount" type="number" step="0.01" name="minimum_order_amount" value="{{ old('minimum_order_amount', 0) }}" placeholder="0">
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="starts_at">
                        <i class="fas fa-calendar-alt text-blue-500 mr-2"></i>Valide du
                    </label>
                    <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                           id="starts_at" type="datetime-local" name="starts_at" value="{{ old('starts_at') }}">
                    <p class="text-xs text-gray-500 mt-1">Laissez vide pour activer immédiatement</p>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="expires_at">
                        <i class="fas fa-calendar-times text-blue-500 mr-2"></i>Valide jusqu'au *
                    </label>
                    <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                           id="expires_at" type="datetime-local" name="expires_at" value="{{ old('expires_at') }}" required>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="max_uses">
                        <i class="fas fa-users text-blue-500 mr-2"></i>Utilisations max totales
                    </label>
                    <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                           id="max_uses" type="number" name="max_uses" value="{{ old('max_uses') }}" placeholder="Illimité si vide">
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="max_uses_per_user">
                        <i class="fas fa-user text-blue-500 mr-2"></i>Utilisations max par utilisateur
                    </label>
                    <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                           id="max_uses_per_user" type="number" name="max_uses_per_user" value="{{ old('max_uses_per_user', 1) }}" placeholder="1">
                </div>
            </div>

            <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }} 
                       class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <label for="is_active" class="ml-3 text-sm font-medium text-gray-700">
                    <i class="fas fa-check-circle text-green-500 mr-2"></i>Code promo actif
                </label>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="submit" class="flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl">
                    <i class="fas fa-save mr-2"></i>
                    Créer le code promo
                </button>
                <a href="{{ route('admin.promo-codes') }}" class="flex items-center px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all">
                    <i class="fas fa-times mr-2"></i>
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
