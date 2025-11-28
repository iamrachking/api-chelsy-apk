@extends('admin.layout')

@section('title', 'Créer un Administrateur')

@section('content')
<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-xl shadow-lg border border-gray-200 p-6">
        <div class="mb-6">
            <a href="{{ route('admin.users') }}" class="text-blue-600 hover:text-blue-800 mb-4 inline-flex items-center">
                <i class="fas fa-arrow-left mr-2"></i> Retour
            </a>
            <h1 class="text-3xl font-bold text-gray-800 flex items-center mt-4">
                <i class="fas fa-user-shield text-green-600 mr-3"></i>
                Créer un Administrateur
            </h1>
            <p class="text-gray-500 mt-2">Un mot de passe aléatoire sera généré et envoyé par email à l'administrateur.</p>
        </div>

        <form action="{{ route('admin.users.store-admin') }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 text-sm font-semibold mb-2" for="firstname">
                        <i class="fas fa-user text-blue-500 mr-2"></i>Prénom *
                    </label>
                    <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                           id="firstname" type="text" name="firstname" value="{{ old('firstname') }}" required autofocus>
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
                           id="lastname" type="text" name="lastname" value="{{ old('lastname') }}" required>
                    @error('lastname') 
                        <p class="text-red-500 text-xs mt-1 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                        </p> 
                    @enderror
                </div>
            </div>

            <div>
                <label class="block text-gray-700 text-sm font-semibold mb-2" for="email">
                    <i class="fas fa-envelope text-blue-500 mr-2"></i>Email *
                </label>
                <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                       id="email" type="email" name="email" value="{{ old('email') }}" required>
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
                       id="phone" type="text" name="phone" value="{{ old('phone') }}">
                @error('phone') 
                    <p class="text-red-500 text-xs mt-1 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}
                    </p> 
                @enderror
            </div>

            <div class="bg-blue-50 border-l-4 border-blue-400 p-4 rounded">
                <div class="flex items-start">
                    <i class="fas fa-info-circle text-blue-400 mr-2 mt-1"></i>
                    <div>
                        <p class="text-blue-700 font-semibold mb-1">Information importante</p>
                        <p class="text-blue-600 text-sm">
                            Un mot de passe aléatoire sécurisé sera généré automatiquement et envoyé par email à l'adresse fournie. 
                            L'administrateur pourra se connecter et changer son mot de passe depuis son profil.
                        </p>
                    </div>
                </div>
            </div>

            <div class="flex gap-4 pt-4">
                <button type="submit" class="flex items-center px-6 py-3 bg-gradient-to-r from-green-600 to-green-700 text-white rounded-lg hover:from-green-700 hover:to-green-800 transition-all shadow-lg hover:shadow-xl">
                    <i class="fas fa-user-plus mr-2"></i>
                    Créer l'administrateur
                </button>
                <a href="{{ route('admin.users') }}" class="flex items-center px-6 py-3 bg-gray-200 text-gray-700 rounded-lg hover:bg-gray-300 transition-all">
                    <i class="fas fa-times mr-2"></i>
                    Annuler
                </a>
            </div>
        </form>
    </div>
</div>
@endsection

