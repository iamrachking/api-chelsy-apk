<?php

namespace App\Http\Controllers\Admin\Web;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\User;
use App\Models\Dish;
use App\Models\Review;
use App\Models\Category;
use App\Models\Restaurant;
use App\Models\PromoCode;
use App\Models\FAQ;
use App\Models\Complaint;
use App\Models\Banner;
use App\Services\ImageService;
use App\Services\NotificationService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'orders' => [
                'today' => Order::whereDate('created_at', today())->count(),
                'this_month' => Order::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                'total' => Order::count(),
                'pending' => Order::where('status', 'pending')->count(),
            ],
            'revenue' => [
                'today' => Order::whereDate('created_at', today())
                    ->where('status', '!=', 'cancelled')
                    ->sum('total'),
                'this_month' => Order::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->where('status', '!=', 'cancelled')
                    ->sum('total'),
                'total' => Order::where('status', '!=', 'cancelled')->sum('total'),
            ],
            'users' => [
                'total' => User::count(),
                'new_today' => User::whereDate('created_at', today())->count(),
            ],
            'dishes' => [
                'total' => Dish::count(),
                'available' => Dish::where('is_available', true)->count(),
            ],
            'alerts' => [
                'pending_orders' => Order::where('status', 'pending')->count(),
                'pending_reviews' => Review::where('is_approved', false)->count(),
                'pending_complaints' => Complaint::where('status', 'pending')->count(),
            ],
        ];

        $recentOrders = Order::with(['user', 'restaurant'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $pendingComplaints = Complaint::with(['user'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $pendingReviews = Review::with(['user', 'dish'])
            ->where('is_approved', false)
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentOrders', 'pendingComplaints', 'pendingReviews'));
    }

    public function restaurant()
    {
        $restaurant = Restaurant::first();
        return view('admin.restaurant', compact('restaurant'));
    }

    public function updateRestaurant(Request $request)
    {
        $restaurant = Restaurant::first();
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
            'history' => 'nullable|string',
            'values' => 'nullable|string',
            'chef_name' => 'nullable|string|max:255',
            'team_description' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'delivery_fee_base' => 'nullable|numeric|min:0',
            'delivery_fee_per_km' => 'nullable|numeric|min:0',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'delivery_radius_km' => 'nullable|integer|min:0',
        ]);

        // Upload du logo si présent
        if ($request->hasFile('logo')) {
            // Supprimer l'ancien logo
            if ($restaurant->logo) {
                ImageService::delete($restaurant->logo);
            }
            $validated['logo'] = ImageService::upload($request->file('logo'), 'restaurants');
        }

        // Upload des images si présentes
        $uploadedImages = [];
        if ($request->hasFile('images')) {
            $images = $request->file('images');
            
            // Si c'est un seul fichier, le convertir en tableau
            if (!is_array($images)) {
                $images = [$images];
            }
            
            foreach ($images as $image) {
                if ($image && $image->isValid()) {
                    $uploadedImages[] = ImageService::upload($image, 'restaurants');
                }
            }
        }
        
        // Fusionner avec les images existantes si de nouvelles images ont été uploadées
        if (!empty($uploadedImages)) {
            $existingImages = $restaurant->images ?? [];
            $validated['images'] = array_merge($existingImages, $uploadedImages);
        } else {
            // Si aucune nouvelle image n'est fournie, conserver les images existantes
            unset($validated['images']);
        }

        $restaurant->update($validated);

        return redirect()->route('admin.restaurant')->with('success', 'Restaurant mis à jour avec succès');
    }

    public function showRestaurant()
    {
        $restaurant = Restaurant::first();
        if (!$restaurant) {
            return redirect()->route('admin.restaurant')->with('error', 'Restaurant non trouvé');
        }
        return view('admin.restaurant.show', compact('restaurant'));
    }

    public function deleteRestaurantImage(Request $request, $index)
    {
        $restaurant = Restaurant::first();
        
        if (!$restaurant || !$restaurant->images || !is_array($restaurant->images)) {
            return redirect()->back()->with('error', 'Image non trouvée');
        }

        $images = $restaurant->images;
        $index = (int) $index; // S'assurer que c'est un entier
        
        if (!isset($images[$index])) {
            return redirect()->back()->with('error', 'Image non trouvée');
        }

        // Supprimer l'image du storage
        $imageToDelete = $images[$index];
        if ($imageToDelete && Storage::disk('public')->exists($imageToDelete)) {
            ImageService::delete($imageToDelete);
        }

        // Retirer l'image du tableau
        unset($images[$index]);
        $images = array_values($images); // Réindexer le tableau

        $restaurant->update(['images' => $images]);

        return redirect()->back()->with('success', 'Image supprimée avec succès');
    }

    public function categories()
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.categories.index', compact('categories'));
    }

    public function createCategory()
    {
        return view('admin.categories.create');
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:categories,slug',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'nullable|boolean',
        ]);

        // Upload de l'image si présente
        if ($request->hasFile('image')) {
            $validated['image'] = ImageService::upload($request->file('image'), 'categories');
        }

        $validated['is_active'] = $request->has('is_active');

        Category::create($validated);

        return redirect()->route('admin.categories')->with('success', 'Catégorie créée avec succès');
    }

    public function editCategory($id)
    {
        $category = Category::findOrFail($id);
        return view('admin.categories.edit', compact('category'));
    }

    public function updateCategory(Request $request, $id)
    {
        $category = Category::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|unique:categories,slug,' . $id,
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'nullable|boolean',
        ]);

        // Upload de la nouvelle image si présente
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image
            if ($category->image) {
                ImageService::delete($category->image);
            }
            $validated['image'] = ImageService::upload($request->file('image'), 'categories');
        }

        $validated['is_active'] = $request->has('is_active');

        $category->update($validated);

        return redirect()->route('admin.categories')->with('success', 'Catégorie mise à jour avec succès');
    }

    public function deleteCategory($id)
    {
        $category = Category::findOrFail($id);
        
        // Supprimer l'image associée
        if ($category->image) {
            ImageService::delete($category->image);
        }
        
        $category->delete();
        return redirect()->route('admin.categories')->with('success', 'Catégorie supprimée avec succès');
    }

    public function dishes()
    {
        $dishes = Dish::with('category')->orderBy('created_at', 'desc')->get();
        return view('admin.dishes.index', compact('dishes'));
    }

    public function createDish()
    {
        $categories = Category::where('is_active', true)->get();
        return view('admin.dishes.create', compact('categories'));
    }

    public function storeDish(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_available' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
        ]);

        // Upload de l'image si présente
        if ($request->hasFile('image')) {
            $imagePath = ImageService::upload($request->file('image'), 'dishes');
            $validated['images'] = [$imagePath]; // Stocker dans le tableau images
        } else {
            $validated['images'] = [];
        }
        unset($validated['image']); // Retirer 'image' car on utilise 'images'

        $validated['is_available'] = $request->has('is_available');
        $validated['is_featured'] = $request->has('is_featured');

        // Générer le slug si non fourni
        if (!isset($validated['slug']) || empty($validated['slug'])) {
            $validated['slug'] = Str::slug($validated['name']);
            // S'assurer que le slug est unique
            $baseSlug = $validated['slug'];
            $counter = 1;
            while (Dish::where('slug', $validated['slug'])->exists()) {
                $validated['slug'] = $baseSlug . '-' . $counter;
                $counter++;
            }
        }

        Dish::create($validated);

        return redirect()->route('admin.dishes')->with('success', 'Plat créé avec succès');
    }

    public function showDish($id)
    {
        $dish = Dish::with(['category', 'options', 'reviews.user'])->findOrFail($id);
        return view('admin.dishes.show', compact('dish'));
    }

    public function editDish($id)
    {
        $dish = Dish::findOrFail($id);
        $categories = Category::where('is_active', true)->get();
        return view('admin.dishes.edit', compact('dish', 'categories'));
    }

    public function updateDish(Request $request, $id)
    {
        $dish = Dish::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'category_id' => 'required|exists:categories,id',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_available' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
        ]);

        // Upload de la nouvelle image si présente
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image si elle existe
            if ($dish->image) {
                ImageService::delete($dish->image);
            }
            $imagePath = ImageService::upload($request->file('image'), 'dishes');
            $validated['images'] = [$imagePath]; // Stocker dans le tableau images
        } else {
            // Si aucune nouvelle image n'est fournie, conserver l'ancienne
            // On ne modifie pas 'images' donc l'ancienne image est conservée
            unset($validated['image']);
        }
        unset($validated['image']); // Retirer 'image' car on utilise 'images'

        $validated['is_available'] = $request->has('is_available');
        $validated['is_featured'] = $request->has('is_featured');

        $dish->update($validated);

        return redirect()->route('admin.dishes')->with('success', 'Plat mis à jour avec succès');
    }

    public function deleteDish($id)
    {
        $dish = Dish::findOrFail($id);
        
        // Supprimer les images associées
        if ($dish->images && is_array($dish->images)) {
            foreach ($dish->images as $image) {
                if ($image) {
                    ImageService::delete($image);
                }
            }
        }
        
        $dish->delete();
        return redirect()->route('admin.dishes')->with('success', 'Plat supprimé avec succès');
    }

    public function orders()
    {
        $orders = Order::with(['user', 'restaurant'])->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.orders.index', compact('orders'));
    }

    public function exportOrders(Request $request)
    {
        $orders = Order::with(['user', 'restaurant', 'payment'])
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'commandes_' . now()->format('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($orders) {
            $file = fopen('php://output', 'w');
            
            // BOM UTF-8 pour Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // En-têtes
            fputcsv($file, [
                'ID',
                'Numéro Commande',
                'Client',
                'Email',
                'Téléphone',
                'Type',
                'Total',
                'Statut',
                'Méthode Paiement',
                'Date Commande',
                'Date Livraison',
            ], ';');

            // Données
            foreach ($orders as $order) {
                fputcsv($file, [
                    $order->id,
                    $order->order_number,
                    ($order->user ? $order->user->firstname . ' ' . $order->user->lastname : 'N/A'),
                    $order->user->email ?? 'N/A',
                    $order->user->phone ?? 'N/A',
                    $order->type,
                    number_format($order->total, 0, ',', ' '),
                    $order->status,
                    $order->payment->method ?? 'N/A',
                    $order->created_at->format('d/m/Y H:i'),
                    $order->delivered_at ? $order->delivered_at->format('d/m/Y H:i') : 'N/A',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportUsers(Request $request)
    {
        $users = User::withCount('orders')
            ->orderBy('created_at', 'desc')
            ->get();

        $filename = 'utilisateurs_' . now()->format('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($users) {
            $file = fopen('php://output', 'w');
            
            // BOM UTF-8 pour Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // En-têtes
            fputcsv($file, [
                'ID',
                'Prénom',
                'Nom',
                'Email',
                'Téléphone',
                'Date Naissance',
                'Genre',
                'Admin',
                'Bloqué',
                'Nombre Commandes',
                'Date Inscription',
                'Dernière Connexion',
            ], ';');

            // Données
            foreach ($users as $user) {
                fputcsv($file, [
                    $user->id,
                    $user->firstname,
                    $user->lastname,
                    $user->email,
                    $user->phone ?? 'N/A',
                    $user->birth_date ? $user->birth_date->format('d/m/Y') : 'N/A',
                    $user->gender ?? 'N/A',
                    $user->is_admin ? 'Oui' : 'Non',
                    $user->is_blocked ? 'Oui' : 'Non',
                    $user->orders_count,
                    $user->created_at->format('d/m/Y H:i'),
                    $user->last_login_at ? $user->last_login_at->format('d/m/Y H:i') : 'Jamais',
                ], ';');
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportStats(Request $request)
    {
        $stats = [
            'orders' => [
                'today' => Order::whereDate('created_at', today())->count(),
                'this_month' => Order::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                'total' => Order::count(),
                'pending' => Order::where('status', 'pending')->count(),
                'delivered' => Order::where('status', 'delivered')->count(),
                'cancelled' => Order::where('status', 'cancelled')->count(),
            ],
            'revenue' => [
                'today' => Order::whereDate('created_at', today())
                    ->where('status', '!=', 'cancelled')
                    ->sum('total'),
                'this_month' => Order::whereMonth('created_at', now()->month)
                    ->whereYear('created_at', now()->year)
                    ->where('status', '!=', 'cancelled')
                    ->sum('total'),
                'total' => Order::where('status', '!=', 'cancelled')->sum('total'),
            ],
            'users' => [
                'total' => User::count(),
                'new_today' => User::whereDate('created_at', today())->count(),
                'new_this_month' => User::whereMonth('created_at', now()->month)->whereYear('created_at', now()->year)->count(),
                'blocked' => User::where('is_blocked', true)->count(),
            ],
            'dishes' => [
                'total' => Dish::count(),
                'available' => Dish::where('is_available', true)->count(),
            ],
        ];

        $filename = 'statistiques_' . now()->format('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($stats) {
            $file = fopen('php://output', 'w');
            
            // BOM UTF-8 pour Excel
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // En-têtes
            fputcsv($file, ['Catégorie', 'Métrique', 'Valeur'], ';');

            // Données
            fputcsv($file, ['Commandes', 'Aujourd\'hui', $stats['orders']['today']], ';');
            fputcsv($file, ['Commandes', 'Ce mois', $stats['orders']['this_month']], ';');
            fputcsv($file, ['Commandes', 'Total', $stats['orders']['total']], ';');
            fputcsv($file, ['Commandes', 'En attente', $stats['orders']['pending']], ';');
            fputcsv($file, ['Commandes', 'Livrées', $stats['orders']['delivered']], ';');
            fputcsv($file, ['Commandes', 'Annulées', $stats['orders']['cancelled']], ';');
            
            fputcsv($file, ['Revenus', 'Aujourd\'hui (FCFA)', number_format($stats['revenue']['today'], 0, ',', ' ')], ';');
            fputcsv($file, ['Revenus', 'Ce mois (FCFA)', number_format($stats['revenue']['this_month'], 0, ',', ' ')], ';');
            fputcsv($file, ['Revenus', 'Total (FCFA)', number_format($stats['revenue']['total'], 0, ',', ' ')], ';');
            
            fputcsv($file, ['Utilisateurs', 'Total', $stats['users']['total']], ';');
            fputcsv($file, ['Utilisateurs', 'Nouveaux aujourd\'hui', $stats['users']['new_today']], ';');
            fputcsv($file, ['Utilisateurs', 'Nouveaux ce mois', $stats['users']['new_this_month']], ';');
            fputcsv($file, ['Utilisateurs', 'Bloqués', $stats['users']['blocked']], ';');
            
            fputcsv($file, ['Plats', 'Total', $stats['dishes']['total']], ';');
            fputcsv($file, ['Plats', 'Disponibles', $stats['dishes']['available']], ';');

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function showOrder($id)
    {
        $order = Order::with(['user', 'restaurant', 'items.dish'])->findOrFail($id);
        return view('admin.orders.show', compact('order'));
    }

    public function updateOrderStatus(Request $request, $id)
    {
        $order = Order::with('user')->findOrFail($id);
        $oldStatus = $order->status;
        $order->update(['status' => $request->status]);

        // Envoyer une notification si le statut a changé
        if ($oldStatus !== $request->status && $order->user) {
            try {
                $notificationService = new NotificationService();
                $notificationService->sendOrderStatusUpdate($order->user, $order->fresh(), $request->status);
            } catch (\Exception $e) {
                Log::error('Erreur lors de l\'envoi de notification de changement de statut', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->back()->with('success', 'Statut de la commande mis à jour');
    }

    public function reviews()
    {
        $reviews = Review::with(['user', 'dish', 'order'])->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.reviews.index', compact('reviews'));
    }

    public function approveReview($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['is_approved' => true]);
        return redirect()->back()->with('success', 'Avis approuvé');
    }

    public function rejectReview($id)
    {
        $review = Review::findOrFail($id);
        $review->update(['is_approved' => false]);
        return redirect()->back()->with('success', 'Avis rejeté');
    }

    public function promoCodes()
    {
        $promoCodes = PromoCode::orderBy('created_at', 'desc')->get();
        return view('admin.promo-codes.index', compact('promoCodes'));
    }

    public function createPromoCode()
    {
        return view('admin.promo-codes.create');
    }

    public function storePromoCode(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:promo_codes,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'starts_at' => 'nullable|date',
            'expires_at' => 'required|date',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        // Si starts_at n'est pas fourni, utiliser la date/heure actuelle
        if (empty($validated['starts_at'])) {
            $validated['starts_at'] = now();
        }

        // Vérifier que expires_at est après starts_at
        if (isset($validated['expires_at']) && $validated['expires_at'] <= $validated['starts_at']) {
            return redirect()->back()->withErrors(['expires_at' => 'La date d\'expiration doit être après la date de début.'])->withInput();
        }

        // Convertir les valeurs boolean
        $validated['is_active'] = $request->has('is_active') ? (bool)$request->is_active : true;

        PromoCode::create($validated);

        return redirect()->route('admin.promo-codes')->with('success', 'Code promo créé avec succès');
    }

    public function showPromoCode($id)
    {
        $promoCode = PromoCode::with(['usages.user', 'orders'])->findOrFail($id);
        
        $stats = [
            'total_uses' => $promoCode->usages()->count(),
            'total_orders' => $promoCode->orders()->count(),
            'total_discount' => $promoCode->orders()->sum('discount_amount'),
            'unique_users' => $promoCode->usages()->distinct('user_id')->count('user_id'),
        ];

        return view('admin.promo-codes.show', compact('promoCode', 'stats'));
    }

    public function editPromoCode($id)
    {
        $promoCode = PromoCode::findOrFail($id);
        return view('admin.promo-codes.edit', compact('promoCode'));
    }

    public function updatePromoCode(Request $request, $id)
    {
        $promoCode = PromoCode::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|unique:promo_codes,code,' . $id,
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:percentage,fixed',
            'value' => 'required|numeric|min:0',
            'minimum_order_amount' => 'nullable|numeric|min:0',
            'starts_at' => 'nullable|date',
            'expires_at' => 'required|date',
            'max_uses' => 'nullable|integer|min:1',
            'max_uses_per_user' => 'nullable|integer|min:1',
            'is_active' => 'nullable|boolean',
        ]);

        // Si starts_at n'est pas fourni, utiliser la date/heure actuelle
        if (empty($validated['starts_at'])) {
            $validated['starts_at'] = now();
        }

        // Vérifier que expires_at est après starts_at
        if (isset($validated['expires_at']) && $validated['expires_at'] <= $validated['starts_at']) {
            return redirect()->back()->withErrors(['expires_at' => 'La date d\'expiration doit être après la date de début.'])->withInput();
        }

        // Convertir les valeurs boolean
        $validated['is_active'] = $request->has('is_active') ? (bool)$request->is_active : false;

        $promoCode->update($validated);

        return redirect()->route('admin.promo-codes')->with('success', 'Code promo modifié avec succès');
    }

    public function deletePromoCode($id)
    {
        $promoCode = PromoCode::findOrFail($id);
        
        // Vérifier s'il y a des utilisations
        if ($promoCode->usages()->count() > 0) {
            return redirect()->back()->with('error', 'Impossible de supprimer ce code promo car il a déjà été utilisé.');
        }

        $promoCode->delete();

        return redirect()->route('admin.promo-codes')->with('success', 'Code promo supprimé avec succès');
    }

    public function faqs()
    {
        $faqs = FAQ::orderBy('order')->get();
        return view('admin.faqs.index', compact('faqs'));
    }

    public function createFAQ()
    {
        return view('admin.faqs.create');
    }

    public function storeFAQ(Request $request)
    {
        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'order' => 'nullable|integer',
            'is_active' => 'nullable',
        ]);

        // Gérer la checkbox : si elle n'est pas cochée, elle n'est pas envoyée
        $validated['is_active'] = $request->has('is_active') ? true : false;

        FAQ::create($validated);

        return redirect()->route('admin.faqs')->with('success', 'FAQ créée avec succès');
    }

    public function editFAQ($id)
    {
        $faq = FAQ::findOrFail($id);
        return view('admin.faqs.edit', compact('faq'));
    }

    public function updateFAQ(Request $request, $id)
    {
        $faq = FAQ::findOrFail($id);

        $validated = $request->validate([
            'question' => 'required|string',
            'answer' => 'required|string',
            'order' => 'nullable|integer',
            'is_active' => 'nullable',
        ]);

        // Gérer la checkbox : si elle n'est pas cochée, elle n'est pas envoyée
        $validated['is_active'] = $request->has('is_active') ? true : false;

        $faq->update($validated);

        return redirect()->route('admin.faqs')->with('success', 'FAQ modifiée avec succès');
    }

    public function deleteFAQ($id)
    {
        $faq = FAQ::findOrFail($id);
        $faq->delete();

        return redirect()->route('admin.faqs')->with('success', 'FAQ supprimée avec succès');
    }

    public function users()
    {
        $users = User::orderBy('created_at', 'desc')->paginate(10);
        $adminCount = User::where('is_admin', true)->count();
        return view('admin.users.index', compact('users', 'adminCount'));
    }

    public function showUser($id)
    {
        $user = User::with(['orders', 'addresses'])->findOrFail($id);
        return view('admin.users.show', compact('user'));
    }

    public function createAdmin()
    {
        return view('admin.users.create-admin');
    }

    public function storeAdmin(Request $request)
    {
        $validated = $request->validate([
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'phone' => 'nullable|string|max:20',
        ]);

        // Générer un mot de passe aléatoire
        $password = Str::random(12);
        $validated['password'] = Hash::make($password);
        $validated['is_admin'] = true;
        $validated['email_verified'] = true;

        $user = User::create($validated);

        // Envoyer l'email avec le mot de passe
        $emailSent = false;
        try {
            Mail::to($user->email)->send(
                new \App\Mail\AdminCredentialsMail($user, $password)
            );
            $emailSent = true;
        } catch (\Exception $e) {
            // Si l'envoi d'email échoue, on continue quand même
            Log::error('Erreur envoi email admin: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
        }

        if ($emailSent) {
            return redirect()->route('admin.users')->with('success', 'Admin créé avec succès. Le mot de passe a été envoyé par email à ' . $user->email);
        } else {
            // Stocker le mot de passe en session pour l'afficher dans une SweetAlert
            $request->session()->flash('admin_password', $password);
            $request->session()->flash('admin_email', $user->email);
            $request->session()->flash('admin_name', $user->firstname . ' ' . $user->lastname);
            return redirect()->route('admin.users')->with('warning', 'Admin créé avec succès, mais l\'email n\'a pas pu être envoyé. Le mot de passe sera affiché ci-dessous.');
        }
    }

    public function toggleBlockUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $adminCount = User::where('is_admin', true)->count();
        $currentUser = $request->user();

        // Vérifier qu'on ne bloque pas le dernier admin
        if ($user->is_admin && $adminCount <= 1) {
            return redirect()->back()->with('error', 'Impossible de bloquer le dernier administrateur.');
        }

        // Vérifier qu'on ne bloque pas son propre compte
        if ($user->id === $currentUser->id && $user->is_admin && $adminCount <= 1) {
            return redirect()->back()->with('error', 'Impossible de bloquer votre propre compte si vous êtes le seul administrateur.');
        }

        $isBlocked = !$user->is_blocked;
        $user->update(['is_blocked' => $isBlocked]);

        $message = $isBlocked ? 'Utilisateur bloqué' : 'Utilisateur débloqué';
        return redirect()->back()->with('success', $message);
    }

    public function deleteUser(Request $request, $id)
    {
        $user = User::findOrFail($id);
        $adminCount = User::where('is_admin', true)->count();
        $currentUser = $request->user();

        // Vérifier qu'on ne supprime pas le dernier admin
        if ($user->is_admin && $adminCount <= 1) {
            return redirect()->back()->with('error', 'Impossible de supprimer le dernier administrateur.');
        }

        // Vérifier qu'on ne supprime pas son propre compte si on est le seul admin
        if ($user->id === $currentUser->id && $user->is_admin && $adminCount <= 1) {
            return redirect()->back()->with('error', 'Impossible de supprimer votre propre compte si vous êtes le seul administrateur.');
        }

        // Supprimer l'avatar si présent
        if ($user->avatar) {
            ImageService::delete($user->avatar);
        }

        $user->delete();

        return redirect()->route('admin.users')->with('success', 'Utilisateur supprimé avec succès');
    }

    // ========== RÉCLAMATIONS ==========
    
    public function complaints(Request $request)
    {
        $query = Complaint::with(['user', 'order']);

        // Filtre par statut si fourni
        if ($request->has('status') && $request->status !== '') {
            $query->where('status', $request->status);
        }

        $complaints = $query->orderBy('created_at', 'desc')->paginate(15);
        
        $stats = [
            'total' => Complaint::count(),
            'pending' => Complaint::where('status', 'pending')->count(),
            'resolved' => Complaint::where('status', 'resolved')->count(),
            'in_progress' => Complaint::where('status', 'in_progress')->count(),
        ];

        return view('admin.complaints.index', compact('complaints', 'stats'));
    }

    public function showComplaint($id)
    {
        $complaint = Complaint::with(['user', 'order'])->findOrFail($id);
        return view('admin.complaints.show', compact('complaint'));
    }

    public function updateComplaintStatus(Request $request, $id)
    {
        $complaint = Complaint::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:pending,in_progress,resolved,closed',
            'admin_response' => 'nullable|string|max:2000',
        ]);

        $complaint->status = $validated['status'];
        
        if (isset($validated['admin_response'])) {
            $complaint->admin_response = $validated['admin_response'];
        }

        if (in_array($validated['status'], ['resolved', 'closed']) && !$complaint->resolved_at) {
            $complaint->resolved_at = now();
        }

        $complaint->save();

        // Envoyer une notification si une réponse admin a été ajoutée ou si le statut a changé
        if (isset($validated['admin_response']) && $complaint->user) {
            try {
                $notificationService = new NotificationService();
                $notificationService->sendComplaintResponse($complaint->user, $complaint->fresh());
            } catch (\Exception $e) {
                Log::error('Erreur lors de l\'envoi de notification de réponse à la réclamation', [
                    'complaint_id' => $complaint->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return redirect()->route('admin.complaints.show', $id)->with('success', 'Statut de la réclamation mis à jour avec succès');
    }

    // ========== IMPORT DE DONNÉES ==========
    
    public function showImportCategories()
    {
        return view('admin.categories.import');
    }

    public function importCategories(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:5120', // 5MB max
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        
        // Lire la première ligne (en-têtes)
        $headers = fgetcsv($handle, 1000, ';');
        if (!$headers) {
            return redirect()->back()->with('error', 'Le fichier CSV est vide ou invalide.');
        }

        // Normaliser les en-têtes (supprimer les espaces, convertir en minuscules)
        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);

        $expectedHeaders = ['nom', 'slug', 'description', 'ordre', 'actif'];
        $headerMap = [];
        
        foreach ($expectedHeaders as $expected) {
            $index = array_search($expected, $headers);
            if ($index !== false) {
                $headerMap[$expected] = $index;
            }
        }

        if (empty($headerMap)) {
            fclose($handle);
            return redirect()->back()->with('error', 'Format de fichier invalide. Les colonnes attendues sont : Nom, Slug, Description, Ordre, Actif');
        }

        $imported = 0;
        $updated = 0;
        $errors = [];
        $lineNumber = 1;

        while (($row = fgetcsv($handle, 1000, ';')) !== false) {
            $lineNumber++;
            
            if (count($row) < 2) {
                continue; // Ignorer les lignes vides
            }

            try {
                $name = isset($headerMap['nom']) && isset($row[$headerMap['nom']]) ? trim($row[$headerMap['nom']]) : null;
                $slug = isset($headerMap['slug']) && isset($row[$headerMap['slug']]) ? trim($row[$headerMap['slug']]) : null;
                $description = isset($headerMap['description']) && isset($row[$headerMap['description']]) ? trim($row[$headerMap['description']]) : null;
                $order = isset($headerMap['ordre']) && isset($row[$headerMap['ordre']]) ? (int)trim($row[$headerMap['ordre']]) : 0;
                $isActive = isset($headerMap['actif']) && isset($row[$headerMap['actif']]) ? 
                    (in_array(strtolower(trim($row[$headerMap['actif']])), ['1', 'true', 'oui', 'yes', 'o', 'y']) ? true : false) : true;

                if (!$name) {
                    $errors[] = "Ligne $lineNumber : Le nom est obligatoire";
                    continue;
                }

                // Générer le slug si non fourni
                if (!$slug) {
                    $slug = Str::slug($name);
                }

                // Vérifier si la catégorie existe déjà
                $category = Category::where('slug', $slug)->first();

                if ($category) {
                    // Mise à jour
                    $category->update([
                        'name' => $name,
                        'description' => $description,
                        'order' => $order,
                        'is_active' => $isActive,
                    ]);
                    $updated++;
                } else {
                    // Création
                    Category::create([
                        'name' => $name,
                        'slug' => $slug,
                        'description' => $description,
                        'order' => $order,
                        'is_active' => $isActive,
                    ]);
                    $imported++;
                }
            } catch (\Exception $e) {
                $errors[] = "Ligne $lineNumber : " . $e->getMessage();
            }
        }

        fclose($handle);

        $message = "Import terminé : $imported catégorie(s) créée(s), $updated catégorie(s) mise(s) à jour.";
        if (!empty($errors)) {
            $message .= " " . count($errors) . " erreur(s) : " . implode(', ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= " et " . (count($errors) - 5) . " autre(s) erreur(s).";
            }
            return redirect()->back()->with('warning', $message);
        }

        return redirect()->route('admin.categories')->with('success', $message);
    }

    public function showImportDishes()
    {
        $categories = Category::where('is_active', true)->orderBy('name')->get();
        return view('admin.dishes.import', compact('categories'));
    }

    public function importDishes(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:csv,txt|max:10240', // 10MB max
        ]);

        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        
        // Lire la première ligne (en-têtes)
        $headers = fgetcsv($handle, 1000, ';');
        if (!$headers) {
            return redirect()->back()->with('error', 'Le fichier CSV est vide ou invalide.');
        }

        // Normaliser les en-têtes
        $headers = array_map('trim', $headers);
        $headers = array_map('strtolower', $headers);

        $expectedHeaders = ['nom', 'categorie', 'prix', 'prix_promotion', 'description', 'temps_preparation', 'disponible', 'mis_en_avant', 'nouveau', 'vegetarien', 'specialite'];
        $headerMap = [];
        
        foreach ($expectedHeaders as $expected) {
            $index = array_search($expected, $headers);
            if ($index !== false) {
                $headerMap[$expected] = $index;
            }
        }

        if (empty($headerMap) || !isset($headerMap['nom']) || !isset($headerMap['categorie']) || !isset($headerMap['prix'])) {
            fclose($handle);
            return redirect()->back()->with('error', 'Format de fichier invalide. Les colonnes obligatoires sont : Nom, Catégorie, Prix');
        }

        $imported = 0;
        $updated = 0;
        $errors = [];
        $lineNumber = 1;

        while (($row = fgetcsv($handle, 1000, ';')) !== false) {
            $lineNumber++;
            
            if (count($row) < 3) {
                continue;
            }

            try {
                $name = isset($headerMap['nom']) && isset($row[$headerMap['nom']]) ? trim($row[$headerMap['nom']]) : null;
                $categoryName = isset($headerMap['categorie']) && isset($row[$headerMap['categorie']]) ? trim($row[$headerMap['categorie']]) : null;
                $price = isset($headerMap['prix']) && isset($row[$headerMap['prix']]) ? trim($row[$headerMap['prix']]) : null;

                if (!$name || !$categoryName || !$price) {
                    $errors[] = "Ligne $lineNumber : Nom, Catégorie et Prix sont obligatoires";
                    continue;
                }

                // Trouver ou créer la catégorie
                $category = Category::where('name', $categoryName)
                    ->orWhere('slug', Str::slug($categoryName))
                    ->first();

                if (!$category) {
                    $errors[] = "Ligne $lineNumber : Catégorie '$categoryName' introuvable. Veuillez d'abord créer cette catégorie.";
                    continue;
                }

                // Valider le prix
                $price = str_replace([' ', ','], ['', '.'], $price);
                if (!is_numeric($price) || $price < 0) {
                    $errors[] = "Ligne $lineNumber : Prix invalide ($price)";
                    continue;
                }

                $discountPrice = null;
                if (isset($headerMap['prix_promotion']) && isset($row[$headerMap['prix_promotion']]) && trim($row[$headerMap['prix_promotion']])) {
                    $discountPrice = str_replace([' ', ','], ['', '.'], trim($row[$headerMap['prix_promotion']]));
                    if (is_numeric($discountPrice) && $discountPrice >= 0) {
                        $discountPrice = (float)$discountPrice;
                    } else {
                        $discountPrice = null;
                    }
                }

                $description = isset($headerMap['description']) && isset($row[$headerMap['description']]) ? trim($row[$headerMap['description']]) : null;
                $preparationTime = isset($headerMap['temps_preparation']) && isset($row[$headerMap['temps_preparation']]) ? (int)trim($row[$headerMap['temps_preparation']]) : 30;
                $isAvailable = isset($headerMap['disponible']) && isset($row[$headerMap['disponible']]) ? 
                    (in_array(strtolower(trim($row[$headerMap['disponible']])), ['1', 'true', 'oui', 'yes', 'o', 'y']) ? true : false) : true;
                $isFeatured = isset($headerMap['mis_en_avant']) && isset($row[$headerMap['mis_en_avant']]) ? 
                    (in_array(strtolower(trim($row[$headerMap['mis_en_avant']])), ['1', 'true', 'oui', 'yes', 'o', 'y']) ? true : false) : false;
                $isNew = isset($headerMap['nouveau']) && isset($row[$headerMap['nouveau']]) ? 
                    (in_array(strtolower(trim($row[$headerMap['nouveau']])), ['1', 'true', 'oui', 'yes', 'o', 'y']) ? true : false) : false;
                $isVegetarian = isset($headerMap['vegetarien']) && isset($row[$headerMap['vegetarien']]) ? 
                    (in_array(strtolower(trim($row[$headerMap['vegetarien']])), ['1', 'true', 'oui', 'yes', 'o', 'y']) ? true : false) : false;
                $isSpecialty = isset($headerMap['specialite']) && isset($row[$headerMap['specialite']]) ? 
                    (in_array(strtolower(trim($row[$headerMap['specialite']])), ['1', 'true', 'oui', 'yes', 'o', 'y']) ? true : false) : false;

                $slug = Str::slug($name);
                
                // Vérifier si le plat existe déjà (avant de générer un slug unique)
                $dish = Dish::where('slug', $slug)->first();
                
                // S'assurer que le slug est unique si on crée un nouveau plat
                if (!$dish) {
                    $baseSlug = $slug;
                    $counter = 1;
                    while (Dish::where('slug', $slug)->exists()) {
                        $slug = $baseSlug . '-' . $counter;
                        $counter++;
                    }
                }

                $data = [
                    'category_id' => $category->id,
                    'name' => $name,
                    'slug' => $slug,
                    'description' => $description,
                    'price' => (float)$price,
                    'discount_price' => $discountPrice,
                    'preparation_time_minutes' => $preparationTime,
                    'is_available' => $isAvailable,
                    'is_featured' => $isFeatured,
                    'is_new' => $isNew,
                    'is_vegetarian' => $isVegetarian,
                    'is_specialty' => $isSpecialty,
                    'images' => [],
                ];

                if ($dish) {
                    $dish->update($data);
                    $updated++;
                } else {
                    Dish::create($data);
                    $imported++;
                }
            } catch (\Exception $e) {
                $errors[] = "Ligne $lineNumber : " . $e->getMessage();
            }
        }

        fclose($handle);

        $message = "Import terminé : $imported plat(s) créé(s), $updated plat(s) mis à jour.";
        if (!empty($errors)) {
            $message .= " " . count($errors) . " erreur(s) : " . implode(', ', array_slice($errors, 0, 5));
            if (count($errors) > 5) {
                $message .= " et " . (count($errors) - 5) . " autre(s) erreur(s).";
            }
            return redirect()->back()->with('warning', $message);
        }

        return redirect()->route('admin.dishes')->with('success', $message);
    }

    // ==================== BANNIÈRES ====================

    public function banners()
    {
        $banners = Banner::orderBy('order')->orderBy('created_at', 'desc')->get();
        return view('admin.banners.index', compact('banners'));
    }

    public function createBanner()
    {
        return view('admin.banners.create');
    }

    public function storeBanner(Request $request)
    {
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'link' => 'nullable|string|max:500',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        // Upload de l'image
        if ($request->hasFile('image')) {
            $validated['image'] = ImageService::upload($request->file('image'), 'banners');
        }

        $validated['is_active'] = $request->has('is_active');
        $validated['order'] = $request->input('order', 0);

        Banner::create($validated);

        return redirect()->route('admin.banners')->with('success', 'Bannière créée avec succès');
    }

    public function editBanner($id)
    {
        $banner = Banner::findOrFail($id);
        return view('admin.banners.edit', compact('banner'));
    }

    public function updateBanner(Request $request, $id)
    {
        $banner = Banner::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'nullable|string|max:255',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'link' => 'nullable|string|max:500',
            'order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);

        // Upload de la nouvelle image si présente
        if ($request->hasFile('image')) {
            // Supprimer l'ancienne image
            if ($banner->image) {
                ImageService::delete($banner->image);
            }
            $validated['image'] = ImageService::upload($request->file('image'), 'banners');
        }

        $validated['is_active'] = $request->has('is_active');
        $validated['order'] = $request->input('order', $banner->order);

        $banner->update($validated);

        return redirect()->route('admin.banners')->with('success', 'Bannière mise à jour avec succès');
    }

    public function deleteBanner($id)
    {
        $banner = Banner::findOrFail($id);
        
        // Supprimer l'image associée
        if ($banner->image) {
            ImageService::delete($banner->image);
        }
        
        $banner->delete();
        return redirect()->route('admin.banners')->with('success', 'Bannière supprimée avec succès');
    }
}
