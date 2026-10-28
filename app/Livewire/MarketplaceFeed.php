<?php

namespace App\Livewire;

use App\Models\Post;
use App\Models\PostLike;
use App\Models\PostComment;
use App\Services\CloudinaryService;
use Livewire\Component;
use Livewire\WithFileUploads;

class MarketplaceFeed extends Component
{
    use WithFileUploads;

    public string $selectedCategory = 'Todos';
    public string $search = '';

    // Create post modal state
    public bool $showCreateModal = false;
    public string $title = '';
    public string $description = '';
    public string $category = 'Plástico';
    public string $quantity = '';
    public float $price = 0.00;
    public bool $is_donation = false;
    public string $address = 'Zona Central, Bolivia';
    public float $latitude = -16.500000;
    public float $longitude = -68.150000;
    public $image;

    // Comment box state
    public array $newComments = [];

    public array $categories = [
        'Todos',
        'Plástico',
        'Cartón',
        'Metal',
        'Vidrio',
        'Electrónicos',
        'Aceite',
        'Orgánicos',
        'Textiles'
    ];

    protected $rules = [
        'title' => 'required|string|max:150',
        'description' => 'required|string',
        'category' => 'required|string',
        'quantity' => 'nullable|string|max:50',
        'price' => 'nullable|numeric|min:0',
        'address' => 'required|string',
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'image' => 'nullable|image|max:10240', // max 10MB
    ];

    public function selectCategory(string $cat)
    {
        $this->selectedCategory = $cat;
    }

    public function toggleLike(int $postId)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Debes iniciar sesión para interactuar con me gusta.');
        }

        $userId = auth()->id();
        $like = PostLike::where('post_id', $postId)->where('user_id', $userId)->first();

        if ($like) {
            $like->delete();
        } else {
            PostLike::create([
                'post_id' => $postId,
                'user_id' => $userId,
            ]);
        }
    }

    public function addComment(int $postId)
    {
        if (!auth()->check()) {
            return redirect()->route('login')->with('error', 'Debes iniciar sesión para comentar.');
        }

        $commentText = trim($this->newComments[$postId] ?? '');
        if (empty($commentText)) return;

        PostComment::create([
            'post_id' => $postId,
            'user_id' => auth()->id(),
            'comment' => e($commentText),
        ]);

        $this->newComments[$postId] = '';
    }

    public function createPost(CloudinaryService $cloudinary)
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $this->validate();

        $imageUrl = 'https://images.unsplash.com/photo-1532996122724-e3c354a0b15b?auto=format&fit=crop&w=800&q=80';
        $cloudinaryPublicId = null;

        if ($this->image) {
            $upload = $cloudinary->uploadImage($this->image, 'posts');
            $imageUrl = $upload['url'];
            $cloudinaryPublicId = $upload['public_id'];
        }

        Post::create([
            'user_id' => auth()->id(),
            'business_id' => auth()->user()->businesses()->first()?->id,
            'title' => e($this->title),
            'description' => e($this->description),
            'category' => $this->category,
            'quantity' => e($this->quantity),
            'price' => $this->is_donation ? 0.00 : $this->price,
            'is_donation' => $this->is_donation,
            'address' => e($this->address),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'cloudinary_public_id' => $cloudinaryPublicId,
            'image_url' => $imageUrl,
            'status' => 'activo',
        ]);

        $this->reset(['title', 'description', 'quantity', 'price', 'is_donation', 'image', 'showCreateModal']);
        session()->flash('success', '¡Publicación de reciclaje creada con éxito!');
    }

    public function getWhatsAppUrl(Post $post): string
    {
        $phone = $post->business?->phone_whatsapp ?? '+59170000000';
        $cleanPhone = preg_replace('/[^0-9]/', '', $phone);
        $message = rawurlencode("Hola! Vi tu publicación de reciclaje '{$post->title}' en EcoRecicla IoT y estoy interesado.");
        return "https://wa.me/{$cleanPhone}?text={$message}";
    }

    public function render()
    {
        $query = Post::with(['user', 'business', 'likes', 'comments.user'])->latest();

        if ($this->selectedCategory !== 'Todos') {
            $query->where('category', $this->selectedCategory);
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhere('category', 'like', '%' . $this->search . '%');
            });
        }

        return view('livewire.marketplace-feed', [
            'posts' => $query->get(),
        ])->layout('layouts.app');
    }
}
