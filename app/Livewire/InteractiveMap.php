<?php

namespace App\Livewire;

use App\Models\Post;
use App\Models\Business;
use Livewire\Component;

class InteractiveMap extends Component
{
    public function render()
    {
        $posts = Post::with(['user', 'business'])->where('status', 'activo')->get()->map(function ($p) {
            $phone = $p->business?->phone_whatsapp ?? '+59170000000';
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
            $msg = rawurlencode("Hola! Te hablo desde el Mapa de EcoRecicla por tu publicación: {$p->title}");

            return [
                'id' => $p->id,
                'type' => 'post',
                'title' => $p->title,
                'category' => $p->category,
                'price' => $p->is_donation ? 'DONACIÓN' : ('$' . number_format($p->price, 2)),
                'address' => $p->address ?? 'Bolivia',
                'latitude' => (float) $p->latitude,
                'longitude' => (float) $p->longitude,
                'image_url' => $p->image_url,
                'whatsapp_url' => "https://wa.me/{$cleanPhone}?text={$msg}",
            ];
        });

        $businesses = Business::all()->map(function ($b) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $b->phone_whatsapp);
            $msg = rawurlencode("Hola {$b->business_name}, te escribo desde el mapa de la plataforma de reciclaje.");

            return [
                'id' => $b->id,
                'type' => 'business',
                'title' => $b->business_name,
                'category' => $b->business_type,
                'materials' => $b->materials_handled,
                'address' => $b->address,
                'latitude' => (float) $b->latitude,
                'longitude' => (float) $b->longitude,
                'whatsapp_url' => "https://wa.me/{$cleanPhone}?text={$msg}",
            ];
        });

        return view('livewire.interactive-map', [
            'locationsJson' => json_encode($posts->concat($businesses)->values()),
        ])->layout('layouts.app');
    }
}
