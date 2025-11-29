@extends('admin.layout')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Plats')

@section('content')
<div class="space-y-6">
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-hamburger text-blue-600 mr-3"></i>
                    Plats
                </h1>
                <p class="text-gray-500 mt-1">Gérez les plats de votre menu</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('admin.dishes.create') }}" class="flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                    <i class="fas fa-plus mr-2"></i>
                    Nouveau Plat
                </a>
                <a href="{{ route('admin.dishes.import') }}" class="flex items-center px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                    <i class="fas fa-file-import mr-2"></i>
                    Importer
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Plat</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Catégorie</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Prix</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Disponible</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-600 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($dishes as $dish)
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
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
                                    <div class="flex-shrink-0 h-12 w-12 rounded-lg overflow-hidden">
                                        <img src="{{ $imageUrl }}" alt="{{ $dish->name }}" class="h-full w-full object-cover">
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">{{ $dish->name }}</div>
                                        @if($dish->is_featured)
                                            <span class="text-xs text-yellow-600">
                                                <i class="fas fa-star"></i> Mis en avant
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 text-xs font-medium bg-blue-100 text-blue-800 rounded-full">
                                    {{ $dish->category->name ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="text-sm font-semibold text-gray-900">{{ number_format($dish->price, 0, ',', ' ') }} FCFA</span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $dish->is_available ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                    <i class="fas {{ $dish->is_available ? 'fa-check-circle' : 'fa-times-circle' }} mr-1"></i>
                                    {{ $dish->is_available ? 'Oui' : 'Non' }}
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <div class="flex items-center space-x-3">
                                    <a href="{{ route('admin.dishes.show', $dish->id) }}" class="text-green-600 hover:text-green-900 transition-colors" title="Voir les détails">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('admin.dishes.edit', $dish->id) }}" class="text-blue-600 hover:text-blue-900 transition-colors" title="Modifier">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="{{ route('admin.dishes.delete', $dish->id) }}" method="POST" class="inline delete-form">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 transition-colors" title="Supprimer">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-8 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-2 block"></i>
                                Aucun plat
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
