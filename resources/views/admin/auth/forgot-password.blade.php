<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mot de passe oublié - CHELSY Restaurant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-blue-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-12 text-center">
                <div class="inline-block bg-white bg-opacity-20 rounded-full p-4 mb-4">
                    <i class="fas fa-key text-white text-4xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">Mot de passe oublié</h1>
                <p class="text-blue-100">Réinitialisez votre mot de passe</p>
            </div>

            <!-- Form -->
            <div class="px-8 py-8">
                @if($errors->any())
                    <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-400 mr-2"></i>
                            <div>
                                <p class="text-red-700 font-bold">Erreur</p>
                                <ul class="list-disc list-inside text-red-600 mt-2 text-sm">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                @if(session('status'))
                    <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-400 mr-2"></i>
                            <p class="text-green-700">{{ session('status') }}</p>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.password.email') }}" class="space-y-6">
                    @csrf
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="email">
                            <i class="fas fa-envelope text-blue-500 mr-2"></i>Adresse email
                        </label>
                        <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                               id="email" type="email" name="email" value="{{ old('email') }}" required 
                               placeholder="admin@chelsy-restaurant.bj" autofocus>
                        <p class="text-xs text-gray-500 mt-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Entrez votre adresse email et nous vous enverrons un lien de réinitialisation.
                        </p>
                    </div>

                    <button type="submit" class="w-full flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                        <i class="fas fa-paper-plane mr-2"></i>
                        Envoyer le lien de réinitialisation
                    </button>
                </form>

                <div class="mt-6 text-center">
                    <a href="{{ route('admin.login') }}" class="text-sm text-blue-600 hover:text-blue-800 hover:underline">
                        <i class="fas fa-arrow-left mr-1"></i>Retour à la connexion
                    </a>
                </div>
            </div>
        </div>
    </div>
</body>
</html>

