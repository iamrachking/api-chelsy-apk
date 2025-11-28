@extends('admin.layout')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Modifier le Plat')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
        <div class="mb-6">
            <a href="{{ route('admin.dishes') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Retour
            </a>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center mt-4">
                <i class="fas fa-edit text-blue-600 mr-3"></i>
                Modifier le Plat
            </h1>
        </div>

        <form action="{{ route('admin.dishes.update', $dish->id) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="name">
                        <i class="fas fa-utensils text-blue-500 mr-2"></i>Nom du Plat * 
                    </label>
                    <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                           id="name" type="text" name="name" value="{{ old('name', $dish->name) }}" required>
                    @error('name') 
                        <p class="text-red-500 text-xs mt-1 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p> 
                    @enderror
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="category_id">
                        <i class="fas fa-folder text-blue-500 mr-2"></i>Catégorie *
                    </label>
                    <select class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                            id="category_id" name="category_id" required>
                        <option value="">Sélectionner une catégorie</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('category_id', $dish->category_id) == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('category_id') 
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
                          id="description" name="description" rows="4">{{ old('description', $dish->description) }}</textarea>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="price">
                        <i class="fas fa-money-bill-wave text-blue-500 mr-2"></i>Prix (FCFA) *
                    </label>
                    <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                           id="price" type="number" step="0.01" name="price" value="{{ old('price', $dish->price) }}" required>
                    @error('price') 
                        <p class="text-red-500 text-xs mt-1 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p> 
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="image">
                    <i class="fas fa-image text-blue-500 mr-2"></i>Image du Plat
                </label>
                @php
                    $images = is_array($dish->images) ? $dish->images : [];
                    $currentImage = !empty($images) ? $images[0] : null;
                    $hasValidImage = $currentImage && Storage::disk('public')->exists($currentImage);
                @endphp
                @if($hasValidImage)
                    <div class="mb-4 p-4 bg-gray-50 rounded-lg border-2 border-blue-200">
                        <p class="text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-image text-blue-500 mr-2"></i>
                            Image actuelle
                        </p>
                        <div class="relative inline-block">
                            <img src="{{ Storage::url($currentImage) }}" alt="Image actuelle" class="max-w-md rounded-lg shadow-md">
                            <div class="absolute top-2 right-2 bg-blue-600 text-white px-3 py-1 rounded-full text-xs font-semibold">
                                Image actuelle
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Si vous ne choisissez pas de nouvelle image, l'image actuelle sera conservée.
                        </p>
                    </div>
                @else
                    <div class="mb-4 p-4 bg-gray-50 rounded-lg border-2 border-gray-200">
                        <p class="text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-image text-gray-500 mr-2"></i>
                            Aucune image actuelle
                        </p>
                        <div class="relative inline-block">
                            <img src="{{ asset('images/default_dish.png') }}" alt="Image par défaut" class="max-w-md rounded-lg shadow-md opacity-50">
                            <div class="absolute top-2 right-2 bg-gray-500 text-white px-3 py-1 rounded-full text-xs font-semibold">
                                Image par défaut
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            L'image par défaut sera utilisée si vous n'ajoutez pas d'image.
                        </p>
                    </div>
                @endif
                <div class="mt-1 flex items-center space-x-5">
                    <label for="image" class="cursor-pointer flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 border-dashed rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                            <p class="text-sm text-gray-500">
                                <span class="font-semibold">Cliquez pour télécharger</span> ou glissez-déposez
                            </p>
                            <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF, WEBP (MAX. 2MB)</p>
                            @if($currentImage)
                                <p class="text-xs text-orange-600 mt-2 font-semibold">
                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                    Une nouvelle image remplacera l'ancienne
                                </p>
                            @endif
                        </div>
                        <input type="file" id="image" name="image" class="hidden" accept="image/*" onchange="previewImage(this)">
                    </label>
                </div>
                <div id="imagePreview" class="mt-4 hidden">
                    <p class="text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-eye text-green-500 mr-2"></i>
                        Aperçu de la nouvelle image
                    </p>
                    <img id="previewImg" src="" alt="Aperçu" class="max-w-md rounded-lg shadow-md border-2 border-green-200">
                </div>
                @error('image') 
                    <p class="text-red-500 text-xs mt-1 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                    </p> 
                @enderror
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                    <input type="checkbox" id="is_available" name="is_available" value="1" {{ old('is_available', $dish->is_available) ? 'checked' : '' }} 
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="is_available" class="ml-3 text-sm font-medium text-gray-700">
                        <i class="fas fa-check-circle text-green-500 mr-2"></i>Disponible
                    </label>
                </div>

                <div class="flex items-center p-4 bg-gray-50 rounded-lg">
                    <input type="checkbox" id="is_featured" name="is_featured" value="1" {{ old('is_featured', $dish->is_featured) ? 'checked' : '' }} 
                           class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                    <label for="is_featured" class="ml-3 text-sm font-medium text-gray-700">
                        <i class="fas fa-star text-yellow-500 mr-2"></i>Mis en avant
                    </label>
                </div>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="submit" class="flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl">
                    <i class="fas fa-save mr-2"></i>
                    Enregistrer les modifications
                </button>
                <a href="{{ route('admin.dishes') }}" class="flex items-center px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all">
                    <i class="fas fa-times mr-2"></i>
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.classList.remove('hidden');
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.classList.add('hidden');
    }
}
</script>
@endsection
