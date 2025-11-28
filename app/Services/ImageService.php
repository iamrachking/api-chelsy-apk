<?php

namespace App\Services;

use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageService
{
    /**
     * Upload et traiter une image
     */
    public static function upload(UploadedFile $file, string $folder = 'images', int $width = 800, int $height = 800): string
    {
        // Générer un nom unique
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $folder . '/' . $filename;
        $fullPath = storage_path('app/public/' . $path);

        // Créer le dossier s'il n'existe pas
        $directory = dirname($fullPath);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        // Créer l'image avec Intervention Image v3
        $manager = new ImageManager(new Driver());
        $image = $manager->read($file->getRealPath());
        
        // Redimensionner en gardant les proportions
        $image->cover($width, $height);
        
        // Sauvegarder l'image
        $image->toJpeg(85)->save($fullPath);
        
        return $path;
    }

    /**
     * Supprimer une image
     */
    public static function delete(string $path): bool
    {
        if ($path && Storage::disk('public')->exists($path)) {
            return Storage::disk('public')->delete($path);
        }
        return false;
    }

    /**
     * Obtenir l'URL publique d'une image
     */
    public static function url(?string $path): ?string
    {
        if (!$path) {
            return null;
        }
        
        return Storage::disk('public')->url($path);
    }
}

