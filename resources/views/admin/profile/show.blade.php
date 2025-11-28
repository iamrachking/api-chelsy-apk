@extends('admin.layout')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Mon Profil')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6 mb-6">
        <h1 class="text-3xl font-bold text-gray-800 flex items-center mb-6">
            <i class="fas fa-user-circle text-blue-600 mr-3"></i>
            Mon Profil
        </h1>

        <!-- Informations Personnelles -->
        <div class="mb-8">
            <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center">
                <i class="fas fa-user text-blue-500 mr-2"></i>
                Informations Personnelles
            </h2>
            
            <form action="{{ route('admin.profile.update') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')
                
                <div class="flex items-center space-x-6 mb-6">
                    <div class="flex-shrink-0">
                        @if(auth()->user()->avatar)
                            <img src="{{ Storage::url(auth()->user()->avatar) }}" alt="Avatar" class="w-24 h-24 rounded-full object-cover border-4 border-blue-500">
                        @else
                            <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-500 to-blue-700 flex items-center justify-center border-4 border-blue-500">
                                <i class="fas fa-user text-white text-3xl"></i>
                            </div>
                        @endif
                    </div>
                    <div>
                        <label for="avatar" class="cursor-pointer inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            <i class="fas fa-camera mr-2"></i>
                            Changer la photo
                        </label>
                        <input type="file" id="avatar" name="avatar" class="hidden" accept="image/*" onchange="previewAvatar(this)">
                        <p class="text-sm text-gray-500 mt-2">PNG, JPG, GIF, WEBP (MAX. 2MB)</p>
                    </div>
                    <div id="avatarPreview" class="hidden">
                        <img id="previewAvatarImg" src="" alt="Aperçu" class="w-24 h-24 rounded-full object-cover border-4 border-blue-500">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="firstname">
                            <i class="fas fa-user text-blue-500 mr-2"></i>Prénom *
                        </label>
                        <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                               id="firstname" type="text" name="firstname" value="{{ old('firstname', auth()->user()->firstname) }}" required>
                        @error('firstname') 
                            <p class="text-red-500 text-xs mt-1 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                            </p> 
                        @enderror
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="lastname">
                            <i class="fas fa-user text-blue-500 mr-2"></i>Nom *
                        </label>
                        <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                               id="lastname" type="text" name="lastname" value="{{ old('lastname', auth()->user()->lastname) }}" required>
                        @error('lastname') 
                            <p class="text-red-500 text-xs mt-1 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                            </p> 
                        @enderror
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="email">
                            <i class="fas fa-envelope text-blue-500 mr-2"></i>Email *
                        </label>
                        <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                               id="email" type="email" name="email" value="{{ old('email', auth()->user()->email) }}" required>
                        @error('email') 
                            <p class="text-red-500 text-xs mt-1 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                            </p> 
                        @enderror
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="phone">
                            <i class="fas fa-phone text-blue-500 mr-2"></i>Téléphone
                        </label>
                        <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                               id="phone" type="text" name="phone" value="{{ old('phone', auth()->user()->phone) }}">
                        @error('phone') 
                            <p class="text-red-500 text-xs mt-1 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                            </p> 
                        @enderror
                    </div>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="flex items-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl">
                        <i class="fas fa-save mr-2"></i>
                        Enregistrer les modifications
                    </button>
                </div>
            </form>
        </div>

        <!-- Changement de Mot de Passe -->
        <div class="border-t border-gray-200 pt-8">
            <h2 class="text-xl font-semibold text-gray-700 mb-4 flex items-center">
                <i class="fas fa-lock text-blue-500 mr-2"></i>
                Changer le Mot de Passe
            </h2>
            
            <form action="{{ route('admin.profile.password') }}" method="POST" class="space-y-6">
                @csrf
                @method('PUT')
                
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="current_password">
                        <i class="fas fa-key text-blue-500 mr-2"></i>Mot de passe actuel *
                    </label>
                    <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                           id="current_password" type="password" name="current_password" required>
                    @error('current_password') 
                        <p class="text-red-500 text-xs mt-1 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p> 
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="password">
                            <i class="fas fa-lock text-blue-500 mr-2"></i>Nouveau mot de passe *
                        </label>
                        <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                               id="password" type="password" name="password" required>
                        @error('password') 
                            <p class="text-red-500 text-xs mt-1 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                            </p> 
                        @enderror
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="password_confirmation">
                            <i class="fas fa-lock text-blue-500 mr-2"></i>Confirmer le mot de passe *
                        </label>
                        <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                               id="password_confirmation" type="password" name="password_confirmation" required>
                    </div>
                </div>

                <div class="flex gap-4 pt-4">
                    <button type="submit" class="flex items-center px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all shadow-lg hover:shadow-xl">
                        <i class="fas fa-key mr-2"></i>
                        Changer le mot de passe
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function previewAvatar(input) {
    const preview = document.getElementById('avatarPreview');
    const previewImg = document.getElementById('previewAvatarImg');
    
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
