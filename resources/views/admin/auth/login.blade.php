<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion Admin - CHELSY Restaurant</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/toastr@2.1.4/toastr.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/toastr@2.1.4/toastr.min.js"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-blue-50 min-h-screen flex items-center justify-center">
    <div class="max-w-md w-full">
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <!-- Header -->
            <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-8 py-12 text-center">
                <div class="inline-block bg-white bg-opacity-20 rounded-full p-4 mb-4">
                    <i class="fas fa-utensils text-white text-4xl"></i>
                </div>
                <h1 class="text-3xl font-bold text-white mb-2">CHELSY Restaurant</h1>
                <p class="text-blue-100">Espace Administrateur</p>
            </div>

            <!-- Form -->
            <div class="px-8 py-8">
                @if($errors->any())
                    <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4 rounded">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-red-400 mr-2"></i>
                            <div>
                                <p class="text-red-700 font-bold">Erreur de connexion</p>
                                <ul class="list-disc list-inside text-red-600 mt-2 text-sm">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        </div>
                    </div>
                @endif

                <form method="POST" action="{{ route('admin.login') }}" class="space-y-6">
                    @csrf
                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="email">
                            <i class="fas fa-envelope text-blue-500 mr-2"></i>Email
                        </label>
                        <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                               id="email" type="email" name="email" value="{{ old('email') }}" required 
                               placeholder="admin@chelsy-restaurant.bj">
                    </div>

                    <div>
                        <label class="block text-gray-700 text-sm font-semibold mb-2" for="password">
                            <i class="fas fa-lock text-blue-500 mr-2"></i>Mot de passe
                        </label>
                        <input class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all" 
                               id="password" type="password" name="password" required 
                               placeholder="••••••••">
                    </div>

                    <div class="flex items-center justify-between">
                        <a href="{{ route('admin.password.request') }}" class="text-sm text-blue-600 hover:text-blue-800 hover:underline">
                            <i class="fas fa-key mr-1"></i>Mot de passe oublié ?
                        </a>
                    </div>

                    <button type="submit" class="w-full flex items-center justify-center px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 text-white rounded-lg hover:from-blue-700 hover:to-blue-800 transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                        <i class="fas fa-sign-in-alt mr-2"></i>
                        Se connecter
                    </button>
                </form>

                <div class="mt-6 p-4 bg-blue-50 rounded-lg border border-blue-200">
                    <p class="text-sm text-blue-800 font-semibold mb-2">
                        <i class="fas fa-info-circle mr-2"></i>Identifiants par défaut :
                    </p>
                    <p class="text-xs text-blue-700">
                        <strong>Email:</strong> admin@chelsy-restaurant.bj<br>
                        <strong>Mot de passe:</strong> admin123
                    </p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
