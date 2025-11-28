@extends('admin.layout')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Gestion du Restaurant')

@section('content')
<div class="max-w-5xl mx-auto">
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6 mb-6">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-3xl font-bold text-gray-800 flex items-center">
                    <i class="fas fa-store text-blue-600 mr-3"></i>
                    Informations du Restaurant
                </h1>
                <p class="text-gray-500 mt-1">Gérez les informations de votre restaurant</p>
            </div>
            <div>
                <a href="{{ route('admin.restaurant.show') }}" class="flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all">
                    <i class="fas fa-eye mr-2"></i>
                    Voir le restaurant
                </a>
            </div>
        </div>

        <form action="{{ route('admin.restaurant.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
            @csrf
            @method('PUT')
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="name">
                        <i class="fas fa-building text-blue-500 mr-2"></i>Nom du Restaurant *
                    </label>
                    <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                           id="name" type="text" name="name" value="{{ old('name', $restaurant->name ?? '') }}" required>
                </div>

                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="phone">
                        <i class="fas fa-phone text-blue-500 mr-2"></i>Téléphone
                    </label>
                    <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                           id="phone" type="text" name="phone" value="{{ old('phone', $restaurant->phone ?? '') }}">
                </div>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="email">
                    <i class="fas fa-envelope text-blue-500 mr-2"></i>Email
                </label>
                <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                       id="email" type="email" name="email" value="{{ old('email', $restaurant->email ?? '') }}">
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="address">
                    <i class="fas fa-map-marker-alt text-blue-500 mr-2"></i>Adresse
                </label>
                <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                       id="address" type="text" name="address" value="{{ old('address', $restaurant->address ?? '') }}">
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="description">
                    <i class="fas fa-align-left text-blue-500 mr-2"></i>Description
                </label>
                <textarea class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                          id="description" name="description" rows="4">{{ old('description', $restaurant->description ?? '') }}</textarea>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="logo">
                    <i class="fas fa-image text-blue-500 mr-2"></i>Logo du Restaurant
                </label>
                @php
                    $hasLogo = $restaurant->logo && Storage::disk('public')->exists($restaurant->logo);
                    $logoUrl = $hasLogo ? Storage::url($restaurant->logo) : asset('images/default_restaurant.png');
                @endphp
                @if($hasLogo)
                    <div class="mb-4 p-4 bg-gray-50 rounded-lg border-2 border-blue-200">
                        <p class="text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-image text-blue-500 mr-2"></i>
                            Logo actuel
                        </p>
                        <div class="relative inline-block">
                            <img src="{{ Storage::url($restaurant->logo) }}" alt="Logo actuel" class="max-w-xs rounded-lg shadow-md">
                            <div class="absolute top-2 right-2 bg-blue-600 text-white px-3 py-1 rounded-full text-xs font-semibold">
                                Logo actuel
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Si vous ne choisissez pas de nouveau logo, le logo actuel sera conservé.
                        </p>
                    </div>
                @else
                    <div class="mb-4 p-4 bg-gray-50 rounded-lg border-2 border-gray-200">
                        <p class="text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-image text-gray-500 mr-2"></i>
                            Aucun logo actuel
                        </p>
                        <div class="relative inline-block">
                            <img src="{{ asset('images/default_restaurant.png') }}" alt="Logo par défaut" class="max-w-xs rounded-lg shadow-md opacity-50">
                            <div class="absolute top-2 right-2 bg-gray-500 text-white px-3 py-1 rounded-full text-xs font-semibold">
                                Logo par défaut
                            </div>
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Le logo par défaut sera utilisé si vous n'ajoutez pas de logo.
                        </p>
                    </div>
                @endif
                <div class="mt-1 flex items-center space-x-5">
                    <label for="logo" class="cursor-pointer flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                            <p class="text-sm text-gray-500">
                                <span class="font-semibold">Cliquez pour télécharger</span> ou glissez-déposez
                            </p>
                            <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF, WEBP (MAX. 2MB)</p>
                        </div>
                        <input type="file" id="logo" name="logo" class="hidden" accept="image/*" onchange="previewImage(this, 'logoPreview')">
                    </label>
                </div>
                <div id="logoPreview" class="mt-4 hidden">
                    <p class="text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-eye text-green-500 mr-2"></i>
                        Aperçu du nouveau logo
                    </p>
                    <img id="previewLogoImg" src="" alt="Aperçu" class="max-w-xs rounded-lg shadow-md">
                </div>
                @error('logo') 
                    <p class="text-red-500 text-xs mt-1 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                    </p> 
                @enderror
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="images">
                    <i class="fas fa-images text-blue-500 mr-2"></i>Galerie d'images du Restaurant
                </label>
                @php
                    $restaurantImages = is_array($restaurant->images) ? $restaurant->images : [];
                    $hasImages = false;
                    $validImagesWithIndex = [];
                    foreach ($restaurantImages as $originalIndex => $img) {
                        if ($img && Storage::disk('public')->exists($img)) {
                            $validImagesWithIndex[] = [
                                'index' => $originalIndex,
                                'path' => $img,
                                'url' => Storage::url($img)
                            ];
                            $hasImages = true;
                        }
                    }
                @endphp
                @if($hasImages)
                    <div class="mb-4 p-4 bg-gray-50 rounded-lg border-2 border-blue-200">
                        <p class="text-sm font-semibold text-gray-700 mb-2 flex items-center">
                            <i class="fas fa-images text-blue-500 mr-2"></i>
                            Images actuelles
                        </p>
                        <div class="grid grid-cols-3 gap-3 p-2">
                            @foreach($validImagesWithIndex as $imageData)
                                <div class="relative group">
                                    <img src="{{ $imageData['url'] }}" alt="Image actuelle" class="w-full h-24 object-cover rounded-lg shadow-md">
                                    <button type="button" onclick="deleteImage({{ $imageData['index'] }})" class="absolute top-2 right-2 bg-red-600 text-white p-1.5 rounded-full hover:bg-red-700 transition-colors opacity-0 group-hover:opacity-100 shadow-lg z-10" title="Supprimer">
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Vous pouvez ajouter de nouvelles images ou supprimer des images existantes en survolant l'image.
                        </p>
                    </div>
                @endif
                <div class="mt-1 flex items-center space-x-5">
                    <label for="images" class="cursor-pointer flex flex-col items-center justify-center w-full h-32 border-2 border-gray-300 border-dashed rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors">
                        <div class="flex flex-col items-center justify-center pt-5 pb-6">
                            <i class="fas fa-cloud-upload-alt text-3xl text-gray-400 mb-2"></i>
                            <p class="text-sm text-gray-500">
                                <span class="font-semibold">Cliquez pour télécharger</span> ou glissez-déposez
                            </p>
                            <p class="text-xs text-gray-500 mt-1">PNG, JPG, GIF, WEBP (MAX. 2MB) - Plusieurs images possibles</p>
                        </div>
                        <input type="file" id="images" name="images[]" class="hidden" accept="image/*" multiple onchange="previewImages(this, 'imagesPreview')">
                    </label>
                </div>
                <div id="imagesPreview" class="mt-4 hidden">
                    <p class="text-sm font-semibold text-gray-700 mb-2 flex items-center">
                        <i class="fas fa-eye text-green-500 mr-2"></i>
                        Aperçu des nouvelles images
                    </p>
                    <div id="imagesPreviewContainer" class="grid grid-cols-3 gap-2"></div>
                </div>
                @error('images.*') 
                    <p class="text-red-500 text-xs mt-1 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                    </p> 
                @enderror
            </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="delivery_fee_base">Frais de livraison de base (FCFA)</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                       id="delivery_fee_base" type="number" step="0.01" name="delivery_fee_base" value="{{ old('delivery_fee_base', $restaurant->delivery_fee_base ?? 0) }}">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="delivery_fee_per_km">Frais par km (FCFA)</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                       id="delivery_fee_per_km" type="number" step="0.01" name="delivery_fee_per_km" value="{{ old('delivery_fee_per_km', $restaurant->delivery_fee_per_km ?? 0) }}">
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="minimum_order_amount">Commande minimum (FCFA)</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                       id="minimum_order_amount" type="number" step="0.01" name="minimum_order_amount" value="{{ old('minimum_order_amount', $restaurant->minimum_order_amount ?? 0) }}">
            </div>
            <div>
                <label class="block text-gray-700 text-sm font-bold mb-2" for="delivery_radius_km">Rayon de livraison (km)</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700" 
                       id="delivery_radius_km" type="number" name="delivery_radius_km" value="{{ old('delivery_radius_km', $restaurant->delivery_radius_km ?? 5) }}">
            </div>
        </div>

        <!-- Coordonnées géographiques avec carte -->
        <div class="mb-6">
            <label class="block text-gray-700 text-sm font-semibold mb-2">
                <i class="fas fa-map-marker-alt text-blue-500 mr-2"></i>Coordonnées géographiques
            </label>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="latitude">
                        <i class="fas fa-map-marker-alt text-blue-500 mr-2"></i>Latitude
                    </label>
                    <input class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed" 
                           id="latitude" type="number" step="0.00000001" name="latitude" value="{{ old('latitude', $restaurant->latitude ?? 6.372477) }}" readonly required>
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        Modifiable uniquement via la carte
                    </p>
                </div>
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="longitude">
                        <i class="fas fa-map-marker-alt text-blue-500 mr-2"></i>Longitude
                    </label>
                    <input class="w-full px-4 py-3 border border-gray-300 rounded-lg bg-gray-50 cursor-not-allowed" 
                           id="longitude" type="number" step="0.00000001" name="longitude" value="{{ old('longitude', $restaurant->longitude ?? 2.354006) }}" readonly required>
                    <p class="text-xs text-gray-500 mt-1">
                        <i class="fas fa-info-circle mr-1"></i>
                        Modifiable uniquement via la carte
                    </p>
                </div>
            </div>
            <div class="mb-4">
                <p class="text-sm text-gray-600 mb-2">
                    <i class="fas fa-info-circle text-blue-500 mr-2"></i>
                    Cliquez sur la carte ou déplacez le marqueur pour définir l'emplacement du restaurant
                </p>
                <div id="map" style="height: 400px; border-radius: 8px; border: 2px solid #e5e7eb;"></div>
            </div>
        </div>

            <div class="flex gap-4 pt-4">
                <button type="submit" class="flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl">
                    <i class="fas fa-save mr-2"></i>
                    Enregistrer les modifications
                </button>
                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all">
                    <i class="fas fa-times mr-2"></i>
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>

@push('styles')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
@endpush

@push('scripts')
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
@endpush

<script>
function previewImage(input, previewId) {
    const preview = document.getElementById(previewId);
    const previewImg = document.getElementById(previewId.replace('Preview', 'Img'));
    
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

function previewImages(input, previewId) {
    const preview = document.getElementById(previewId);
    const container = document.getElementById(previewId + 'Container');
    
    if (input.files && input.files.length > 0) {
        container.innerHTML = ''; // Vider le conteneur
        
        Array.from(input.files).forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const imgDiv = document.createElement('div');
                imgDiv.className = 'relative';
                imgDiv.innerHTML = `
                    <img src="${e.target.result}" alt="Aperçu ${index + 1}" class="w-full h-24 object-cover rounded-lg shadow-md">
                    <div class="absolute top-1 right-1 bg-green-600 text-white px-2 py-1 rounded-full text-xs font-semibold">
                        ${index + 1}
                    </div>
                `;
                container.appendChild(imgDiv);
            };
            reader.readAsDataURL(file);
        });
        
        preview.classList.remove('hidden');
    } else {
        preview.classList.add('hidden');
    }
}

// Fonction pour supprimer une image sans formulaire imbriqué
function deleteImage(index) {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: "Cette image sera supprimée définitivement !",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Oui, supprimer !',
        cancelButtonText: 'Annuler',
        reverseButtons: true
    }).then((result) => {
        if (result.isConfirmed) {
            // Créer un formulaire dynamique en dehors du formulaire principal
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = '{{ route("admin.restaurant.delete-image", ":index") }}'.replace(':index', index);
            
            const csrfInput = document.createElement('input');
            csrfInput.type = 'hidden';
            csrfInput.name = '_token';
            csrfInput.value = '{{ csrf_token() }}';
            
            const methodInput = document.createElement('input');
            methodInput.type = 'hidden';
            methodInput.name = '_method';
            methodInput.value = 'DELETE';
            
            form.appendChild(csrfInput);
            form.appendChild(methodInput);
            document.body.appendChild(form);
            form.submit();
        }
    });
}

// Initialisation de la carte Leaflet
document.addEventListener('DOMContentLoaded', function() {
    if (typeof L === 'undefined') {
        console.error('Leaflet n\'est pas chargé');
        return;
    }
    
    const defaultLat = {{ $restaurant->latitude ?? 6.372477 }};
    const defaultLng = {{ $restaurant->longitude ?? 2.354006 }};
    
    // Initialiser la carte
    const map = L.map('map').setView([defaultLat, defaultLng], 13);
    
    // Ajouter les tuiles OpenStreetMap
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors',
        maxZoom: 19
    }).addTo(map);
    
    // Créer un marqueur draggable
    const marker = L.marker([defaultLat, defaultLng], { draggable: true }).addTo(map);
    
    // Fonction pour mettre à jour les coordonnées
    function updateCoordinates(lat, lng) {
        document.getElementById('latitude').value = lat.toFixed(8);
        document.getElementById('longitude').value = lng.toFixed(8);
    }
    
    // Événement quand le marqueur est déplacé
    marker.on('dragend', function(e) {
        const position = marker.getLatLng();
        updateCoordinates(position.lat, position.lng);
    });
    
    // Événement quand on clique sur la carte
    map.on('click', function(e) {
        const lat = e.latlng.lat;
        const lng = e.latlng.lng;
        marker.setLatLng([lat, lng]);
        updateCoordinates(lat, lng);
    });
    
    // Mise à jour initiale
    updateCoordinates(defaultLat, defaultLng);
});
</script>
@endsection

