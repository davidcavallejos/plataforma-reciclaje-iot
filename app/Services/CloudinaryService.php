<?php

namespace App\Services;

use Cloudinary\Cloudinary;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CloudinaryService
{
    protected ?Cloudinary $cloudinary = null;
    protected bool $isConfigured = false;

    public function __construct()
    {
        $cloudName = config('cloudinary.cloud_name');
        $apiKey = config('cloudinary.api_key');
        $apiSecret = config('cloudinary.api_secret');

        if (!empty($cloudName) && !empty($apiKey) && !empty($apiSecret) && $cloudName !== 'demo_cloud') {
            try {
                $this->cloudinary = new Cloudinary([
                    'cloud' => [
                        'cloud_name' => $cloudName,
                        'api_key'    => $apiKey,
                        'api_secret' => $apiSecret,
                    ],
                    'url' => [
                        'secure' => true
                    ]
                ]);
                $this->isConfigured = true;
            } catch (\Throwable $e) {
                Log::warning('Cloudinary SDK initialization warning: ' . $e->getMessage());
                $this->isConfigured = false;
            }
        }
    }

    /**
     * Subir imagen a Cloudinary o almacenamiento optimizado.
     *
     * @param UploadedFile|string $file
     * @param string $folder
     * @return array{url: string, public_id: string}
     */
    public function uploadImage($file, string $folder = 'reciclaje'): array
    {
        if ($this->isConfigured && $this->cloudinary) {
            try {
                $filePath = $file instanceof UploadedFile ? $file->getRealPath() : $file;
                $result = $this->cloudinary->uploadApi()->upload($filePath, [
                    'folder' => 'plataforma_reciclaje/' . $folder,
                    'transformation' => [
                        'quality' => 'auto',
                        'fetch_format' => 'auto',
                        'width' => 1200,
                        'crop' => 'limit',
                    ]
                ]);

                return [
                    'url' => $result['secure_url'] ?? $result['url'],
                    'public_id' => $result['public_id'],
                ];
            } catch (\Throwable $e) {
                Log::error('Cloudinary upload error: ' . $e->getMessage());
            }
        }

        // Fallback local optimizado cuando no hay credenciales Cloudinary activas
        if ($file instanceof UploadedFile) {
            $filename = Str::random(20) . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('uploads/' . $folder, $filename, 'public');
            $url = asset('storage/' . $path);
            return [
                'url' => $url,
                'public_id' => 'local_' . Str::slug($filename),
            ];
        }

        // Caso string URL o base64
        return [
            'url' => is_string($file) ? $file : 'https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?auto=format&fit=crop&w=800&q=80',
            'public_id' => 'fallback_' . Str::random(10),
        ];
    }

    /**
     * Obtener URL optimizada por CDN de Cloudinary
     */
    public function getOptimizedUrl(string $imageUrl, ?string $publicId = null, int $width = 800, int $height = 600): string
    {
        if ($this->isConfigured && $publicId && !str_starts_with($publicId, 'local_') && !str_starts_with($publicId, 'fallback_')) {
            try {
                return (string) $this->cloudinary->image($publicId)
                    ->resize("c_fill,w_{$width},h_{$height},g_auto")
                    ->delivery('q_auto,f_auto');
            } catch (\Throwable $e) {
                return $imageUrl;
            }
        }

        return $imageUrl;
    }

    /**
     * Eliminar recurso de Cloudinary o local
     */
    public function deleteImage(?string $publicId): bool
    {
        if (!$publicId) return false;

        if ($this->isConfigured && $this->cloudinary && !str_starts_with($publicId, 'local_')) {
            try {
                $this->cloudinary->uploadApi()->destroy($publicId);
                return true;
            } catch (\Throwable $e) {
                Log::error('Cloudinary destroy error: ' . $e->getMessage());
                return false;
            }
        }

        return true;
    }
}
