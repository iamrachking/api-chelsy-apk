@extends('admin.layout')

@section('title', 'Détails du Code Promo')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-ticket-alt text-blue-600 mr-3"></i>
                    Code Promo : {{ $promoCode->code }}
                </h1>
                <p class="text-gray-500 mt-1">Détails et statistiques d'utilisation</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.promo-codes.edit', $promoCode->id) }}" class="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all">
                    <i class="fas fa-edit mr-2"></i>
                    Modifier
                </a>
                <a href="{{ route('admin.promo-codes') }}" class="flex items-center px-4 py-2 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Retour
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Informations principales -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Informations du code promo -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                        Informations
                    </h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Code</label>
                            <p class="text-gray-900 font-bold text-lg">{{ $promoCode->code }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Nom</label>
                            <p class="text-gray-900">{{ $promoCode->name }}</p>
                        </div>
                        <div class="col-span-2">
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                            <p class="text-gray-900">{{ $promoCode->description ?? '-' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Type</label>
                            <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full {{ $promoCode->type === 'percentage' ? 'bg-purple-100 text-purple-800' : 'bg-green-100 text-green-800' }}">
                                {{ $promoCode->type === 'percentage' ? 'Pourcentage' : 'Montant fixe' }}
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Valeur</label>
                            <p class="text-gray-900 font-bold">
                                {{ $promoCode->type === 'percentage' ? $promoCode->value . '%' : number_format($promoCode->value, 0, ',', ' ') . ' FCFA' }}
                            </p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Commande minimum</label>
                            <p class="text-gray-900">{{ number_format($promoCode->minimum_order_amount ?? 0, 0, ',', ' ') }} FCFA</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Statut</label>
                            @php
                                $isValid = $promoCode->expires_at && $promoCode->expires_at->isFuture() && $promoCode->is_active;
                            @endphp
                            <span class="px-3 py-1 inline-flex text-sm font-semibold rounded-full {{ $isValid ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                <i class="fas {{ $isValid ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                                {{ $isValid ? 'Actif' : 'Inactif/Expiré' }}
                            </span>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Valide du</label>
                            <p class="text-gray-900">{{ $promoCode->starts_at->format('d/m/Y à H:i') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Valide jusqu'au</label>
                            <p class="text-gray-900">{{ $promoCode->expires_at->format('d/m/Y à H:i') }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Utilisations max totales</label>
                            <p class="text-gray-900">{{ $promoCode->max_uses ?? 'Illimité' }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 mb-1">Utilisations max par utilisateur</label>
                            <p class="text-gray-900">{{ $promoCode->max_uses_per_user ?? 'Illimité' }}</p>
                        </div>
                    </div>
                </div>

                <!-- Historique des utilisations -->
                @if($promoCode->usages->count() > 0)
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-history text-blue-600 mr-2"></i>
                        Historique des utilisations
                    </h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Utilisateur</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Commande</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-600 uppercase">Date</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($promoCode->usages->take(10) as $usage)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            {{ $usage->user->firstname }} {{ $usage->user->lastname }}
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-900">
                                            @if($usage->order)
                                                <a href="{{ route('admin.orders.show', $usage->order->id) }}" class="text-blue-600 hover:text-blue-900">
                                                    #{{ $usage->order->order_number }}
                                                </a>
                                            @else
                                                -
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-gray-500">
                                            {{ $usage->created_at->format('d/m/Y H:i') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>

            <!-- Sidebar - Statistiques -->
            <div class="space-y-6">
                <!-- Statistiques -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Statistiques</h2>
                    <div class="space-y-4">
                        <div class="bg-blue-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Total utilisations</p>
                                    <p class="text-2xl font-bold text-blue-600 mt-1">{{ $stats['total_uses'] }}</p>
                                </div>
                                <i class="fas fa-chart-line text-blue-600 text-2xl"></i>
                            </div>
                        </div>
                        <div class="bg-green-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Total commandes</p>
                                    <p class="text-2xl font-bold text-green-600 mt-1">{{ $stats['total_orders'] }}</p>
                                </div>
                                <i class="fas fa-shopping-cart text-green-600 text-2xl"></i>
                            </div>
                        </div>
                        <div class="bg-purple-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Total réductions</p>
                                    <p class="text-2xl font-bold text-purple-600 mt-1">{{ number_format($stats['total_discount'], 0, ',', ' ') }} FCFA</p>
                                </div>
                                <i class="fas fa-money-bill-wave text-purple-600 text-2xl"></i>
                            </div>
                        </div>
                        <div class="bg-orange-50 rounded-lg p-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-gray-600">Utilisateurs uniques</p>
                                    <p class="text-2xl font-bold text-orange-600 mt-1">{{ $stats['unique_users'] }}</p>
                                </div>
                                <i class="fas fa-users text-orange-600 text-2xl"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Actions rapides -->
                <div class="bg-white border border-gray-200 rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4">Actions</h2>
                    <div class="space-y-3">
                        <a href="{{ route('admin.promo-codes.edit', $promoCode->id) }}" class="block w-full text-center px-4 py-2 bg-green-100 text-green-700 rounded-lg hover:bg-green-200 transition-colors">
                            <i class="fas fa-edit mr-2"></i>Modifier
                        </a>
                        @if($promoCode->usages()->count() === 0)
                        <form action="{{ route('admin.promo-codes.delete', $promoCode->id) }}" method="POST" class="delete-form">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="block w-full text-center px-4 py-2 bg-red-100 text-red-700 rounded-lg hover:bg-red-200 transition-colors">
                                <i class="fas fa-trash mr-2"></i>Supprimer
                            </button>
                        </form>
                        @else
                        <button disabled class="block w-full text-center px-4 py-2 bg-gray-100 text-gray-400 rounded-lg cursor-not-allowed" title="Impossible de supprimer car déjà utilisé">
                            <i class="fas fa-trash mr-2"></i>Supprimer (utilisé)
                        </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

