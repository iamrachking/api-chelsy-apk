@extends('admin.layout')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Détails du Plat')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
        <div class="mb-6">
            <a href="{{ route('admin.dishes') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Retour à la liste
            </a>
            <div class="flex justify-between items-center mt-4">
                <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-hamburger text-blue-600 mr-3"></i>
                    {{ $dish->name }}
                </h1>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.dishes.edit', $dish->id) }}" class="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all">
                        <i class="fas fa-edit mr-2"></i>
                        Modifier
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Image à gauche -->
            <div>
                @php
                    // Vérifier d'abord si images est un tableau non vide
                    $images = is_array($dish->images) ? $dish->images : [];
                    $mainImage = !empty($images) ? $images[0] : null;
                    
                    // Vérifier si l'image existe réellement dans le storage
                    if ($mainImage && Storage::disk('public')->exists($mainImage)) {
                        $imageUrl = Storage::url($mainImage);
                    } else {
                        $imageUrl = asset('images/default_dish.png');
                    }
                @endphp
                <div class="rounded-xl overflow-hidden shadow-lg">
                    <img src="{{ $imageUrl }}" alt="{{ $dish->name }}" class="w-full h-auto object-cover">
                </div>
            </div>

            <!-- Informations à droite -->
            <div class="space-y-6">
                <!-- Informations principales -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-info-circle text-blue-600 mr-2"></i>
                        Informations principales
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Nom du plat</p>
                            <p class="text-lg font-semibold text-gray-900">{{ $dish->name }}</p>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Catégorie</p>
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 text-blue-800">
                                <i class="fas fa-folder mr-2"></i>
                                {{ $dish->category->name ?? 'Non catégorisé' }}
                            </span>
                        </div>
                        <div>
                            <p class="text-sm text-gray-500 mb-1">Prix</p>
                            <p class="text-2xl font-bold text-green-600">
                                {{ number_format($dish->price, 0, ',', ' ') }} FCFA
                            </p>
                        </div>
                        @if($dish->description)
                            <div>
                                <p class="text-sm text-gray-500 mb-1">Description</p>
                                <p class="text-gray-700">{{ $dish->description }}</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Statuts -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-toggle-on text-blue-600 mr-2"></i>
                        Statuts
                    </h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="flex items-center p-3 bg-white rounded-lg">
                            @if($dish->is_available)
                                <i class="fas fa-check-circle text-green-500 text-xl mr-3"></i>
                                <span class="text-sm font-medium text-gray-700">Disponible</span>
                            @else
                                <i class="fas fa-times-circle text-red-500 text-xl mr-3"></i>
                                <span class="text-sm font-medium text-gray-700">Indisponible</span>
                            @endif
                        </div>
                        <div class="flex items-center p-3 bg-white rounded-lg">
                            @if($dish->is_featured)
                                <i class="fas fa-star text-yellow-500 text-xl mr-3"></i>
                                <span class="text-sm font-medium text-gray-700">Mis en avant</span>
                            @else
                                <i class="far fa-star text-gray-400 text-xl mr-3"></i>
                                <span class="text-sm font-medium text-gray-500">Non mis en avant</span>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="bg-gray-50 rounded-lg p-6">
                    <h2 class="text-xl font-bold text-gray-800 mb-4 flex items-center">
                        <i class="fas fa-chart-bar text-blue-600 mr-2"></i>
                        Statistiques
                    </h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-white rounded-lg p-4">
                            <p class="text-sm text-gray-500 mb-1">Commandes</p>
                            <p class="text-2xl font-bold text-blue-600">{{ $dish->order_count ?? 0 }}</p>
                        </div>
                        <div class="bg-white rounded-lg p-4">
                            <p class="text-sm text-gray-500 mb-1">Note moyenne</p>
                            <p class="text-2xl font-bold text-yellow-600">
                                {{ number_format($dish->average_rating ?? 0, 1) }}/5
                                <i class="fas fa-star text-yellow-500 text-lg"></i>
                            </p>
                        </div>
                        <div class="bg-white rounded-lg p-4">
                            <p class="text-sm text-gray-500 mb-1">Avis</p>
                            <p class="text-2xl font-bold text-green-600">{{ $dish->review_count ?? 0 }}</p>
                        </div>
                        <div class="bg-white rounded-lg p-4">
                            <p class="text-sm text-gray-500 mb-1">Date de création</p>
                            <p class="text-sm font-medium text-gray-700">{{ $dish->created_at->format('d/m/Y') }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

