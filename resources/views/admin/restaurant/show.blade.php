@extends('admin.layout')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Détails du Restaurant')

@section('content')
<div class="max-w-6xl mx-auto">
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
        <div class="mb-6">
            <a href="{{ route('admin.restaurant') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Retour à la modification
            </a>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center mt-4">
                <i class="fas fa-store text-blue-600 mr-3"></i>
                {{ $restaurant->name }}
            </h1>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Images à gauche -->
            <div class="space-y-4">
                <!-- Logo -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-image text-blue-500 mr-2"></i>
                        Logo
                    </h3>
                    @php
                        $logoUrl = $restaurant->logo && Storage::disk('public')->exists($restaurant->logo) 
                            ? Storage::url($restaurant->logo) 
                            : asset('images/default_restaurant.png');
                    @endphp
                    <div class="rounded-xl overflow-hidden shadow-lg bg-gray-100 p-4">
                        <img src="{{ $logoUrl }}" alt="Logo du restaurant" class="w-full h-auto object-contain max-h-64 mx-auto">
                    </div>
                </div>

                <!-- Galerie d'images -->
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-images text-blue-500 mr-2"></i>
                        Galerie d'images
                    </h3>
                    @php
                        $images = is_array($restaurant->images) ? $restaurant->images : [];
                        $hasImages = false;
                        $validImages = [];
                        foreach ($images as $originalIndex => $image) {
                            if ($image && Storage::disk('public')->exists($image)) {
                                $validImages[] = [
                                    'url' => Storage::url($image),
                                    'index' => $originalIndex,
                                    'path' => $image
                                ];
                                $hasImages = true;
                            }
                        }
                        if (!$hasImages) {
                            $validImages = [['url' => asset('images/default_restaurant.png'), 'index' => null, 'path' => null]];
                        }
                    @endphp
                    <div class="grid grid-cols-2 gap-4">
                        @foreach($validImages as $imageData)
                            <div class="relative group rounded-lg overflow-hidden shadow-md">
                                <img src="{{ $imageData['url'] }}" alt="Image du restaurant" class="w-full h-48 object-cover">
                                @if($imageData['index'] !== null)
                                    <form action="{{ route('admin.restaurant.delete-image', $imageData['index']) }}" method="POST" class="absolute top-2 right-2 delete-form z-10">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="bg-red-600 text-white p-2 rounded-full hover:bg-red-700 transition-colors opacity-0 group-hover:opacity-100 shadow-lg" title="Supprimer">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Informations à droite -->
            <div class="space-y-6">
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                        Informations générales
                    </h3>
                    <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Nom:</p>
                            <p class="text-gray-800 text-lg">{{ $restaurant->name }}</p>
                        </div>
                        @if($restaurant->description)
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Description:</p>
                            <p class="text-gray-800">{{ $restaurant->description }}</p>
                        </div>
                        @endif
                        @if($restaurant->phone)
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Téléphone:</p>
                            <p class="text-gray-800">{{ $restaurant->phone }}</p>
                        </div>
                        @endif
                        @if($restaurant->email)
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Email:</p>
                            <p class="text-gray-800">{{ $restaurant->email }}</p>
                        </div>
                        @endif
                        @if($restaurant->address)
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Adresse:</p>
                            <p class="text-gray-800">{{ $restaurant->address }}</p>
                        </div>
                        @endif
                        @if($restaurant->latitude && $restaurant->longitude)
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Coordonnées:</p>
                            <p class="text-gray-800">{{ $restaurant->latitude }}, {{ $restaurant->longitude }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                @if($restaurant->history || $restaurant->values || $restaurant->chef_name || $restaurant->team_description)
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-book text-blue-500 mr-2"></i>
                        Histoire & Valeurs
                    </h3>
                    <div class="bg-gray-50 rounded-lg p-4 space-y-3">
                        @if($restaurant->history)
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Histoire:</p>
                            <p class="text-gray-800">{{ $restaurant->history }}</p>
                        </div>
                        @endif
                        @if($restaurant->values)
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Valeurs:</p>
                            <p class="text-gray-800">{{ $restaurant->values }}</p>
                        </div>
                        @endif
                        @if($restaurant->chef_name)
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Chef:</p>
                            <p class="text-gray-800">{{ $restaurant->chef_name }}</p>
                        </div>
                        @endif
                        @if($restaurant->team_description)
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Équipe:</p>
                            <p class="text-gray-800">{{ $restaurant->team_description }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-shipping-fast text-blue-500 mr-2"></i>
                        Informations de livraison
                    </h3>
                    <div class="bg-gray-50 rounded-lg p-4 grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Frais de base:</p>
                            <p class="text-gray-800 font-bold">{{ number_format($restaurant->delivery_fee_base ?? 0, 0, ',', ' ') }} FCFA</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Frais par km:</p>
                            <p class="text-gray-800 font-bold">{{ number_format($restaurant->delivery_fee_per_km ?? 0, 0, ',', ' ') }} FCFA</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Commande minimum:</p>
                            <p class="text-gray-800 font-bold">{{ number_format($restaurant->minimum_order_amount ?? 0, 0, ',', ' ') }} FCFA</p>
                        </div>
                        <div>
                            <p class="text-gray-600 text-sm font-semibold">Rayon de livraison:</p>
                            <p class="text-gray-800 font-bold">{{ $restaurant->delivery_radius_km ?? 0 }} km</p>
                        </div>
                    </div>
                </div>

                @if($restaurant->opening_hours && is_array($restaurant->opening_hours))
                <div>
                    <h3 class="text-lg font-semibold text-gray-700 mb-3 flex items-center">
                        <i class="fas fa-clock text-blue-500 mr-2"></i>
                        Horaires d'ouverture
                    </h3>
                    <div class="bg-gray-50 rounded-lg p-4 space-y-2">
                        @php
                            $daysFr = [
                                'monday' => 'Lundi',
                                'tuesday' => 'Mardi',
                                'wednesday' => 'Mercredi',
                                'thursday' => 'Jeudi',
                                'friday' => 'Vendredi',
                                'saturday' => 'Samedi',
                                'sunday' => 'Dimanche',
                            ];
                        @endphp
                        @foreach($restaurant->opening_hours as $day => $hours)
                            <div class="flex justify-between items-center">
                                <span class="text-gray-700 font-semibold">{{ $daysFr[$day] ?? ucfirst($day) }}:</span>
                                <span class="text-gray-800">
                                    {{ $hours['open'] ?? 'Fermé' }} - {{ $hours['close'] ?? 'Fermé' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>
                @endif

                <div class="flex items-center space-x-3 pt-4">
                    <a href="{{ route('admin.restaurant') }}" class="flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all">
                        <i class="fas fa-edit mr-2"></i>
                        Modifier
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

