<?php

namespace App\Livewire\Admin;

use App\Models\Post;
use App\Services\CloudinaryService;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\WithFileUploads;

class PostManagement extends Component
{
    use WithPagination, WithFileUploads;

    protected $paginationTheme = 'tailwind';

    public string $search = '';
    public string $categoryFilter = '';
    public string $statusFilter = '';

    // Edit Modal state
    public bool $isEditModalOpen = false;
    public ?int $selectedPostId = null;

    // Post edit fields
    public string $title = '';
    public string $description = '';
    public string $category = 'Plástico PET';
    public $price = 0;
    public $quantity = 1;
    public bool $is_donation = false;
    public string $status = 'active';
    public string $address = '';

    // File upload for image modification
    public $newImage;
    public ?string $currentImageUrl = null;
    public ?string $currentPublicId = null;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingCategoryFilter(): void
    {
        $this->resetPage();
    }

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

    public function openEditModal(int $postId): void
    {
        $this->resetValidation();
        $post = Post::findOrFail($postId);

        $this->selectedPostId = $post->id;
        $this->title = $post->title;
        $this->description = $post->description ?? '';
        $this->category = $post->category;
        $this->price = $post->price;
        $this->quantity = $post->quantity;
        $this->is_donation = (bool) $post->is_donation;
        $this->status = $post->status ?? 'active';
        $this->address = $post->address ?? '';
        $this->currentImageUrl = $post->image_url;
        $this->currentPublicId = $post->cloudinary_public_id;
        $this->newImage = null;

        $this->isEditModalOpen = true;
    }

    public function closeEditModal(): void
    {
        $this->isEditModalOpen = false;
        $this->selectedPostId = null;
        $this->newImage = null;
    }

    public function updatePost(CloudinaryService $cloudinaryService): void
    {
        if (!$this->selectedPostId) return;

        $post = Post::findOrFail($this->selectedPostId);

        $validated = $this->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string',
            'price' => 'required|numeric|min:0',
            'quantity' => 'required|numeric|min:0',
            'is_donation' => 'boolean',
            'status' => 'required|in:active,hidden,sold,inactive',
            'address' => 'nullable|string|max:255',
            'newImage' => 'nullable|image|max:10240', // 10MB max
        ], [
            'title.required' => 'El título de la publicación es obligatorio.',
            'category.required' => 'Debe seleccionar una categoría de material.',
            'price.required' => 'El precio debe ser un número válido.',
            'status.in' => 'El estado de la publicación no es válido.',
            'newImage.image' => 'El archivo seleccionado debe ser una imagen válida.',
            'newImage.max' => 'La imagen no debe superar los 10 MB.',
        ]);

        $imageUrl = $post->image_url;
        $publicId = $post->cloudinary_public_id;

        // Clean up previous image & upload new image if specified
        if ($this->newImage) {
            if (!empty($publicId)) {
                $cloudinaryService->deleteImage($publicId);
            }

            $uploadResult = $cloudinaryService->uploadImage($this->newImage, 'posts');
            $imageUrl = $uploadResult['url'];
            $publicId = $uploadResult['public_id'];
        }

        $post->update([
            'title' => $validated['title'],
            'description' => $validated['description'],
            'category' => $validated['category'],
            'price' => $validated['price'],
            'quantity' => $validated['quantity'],
            'is_donation' => $validated['is_donation'],
            'status' => $validated['status'],
            'address' => $validated['address'],
            'image_url' => $imageUrl,
            'cloudinary_public_id' => $publicId,
        ]);

        session()->flash('success', "Publicación #{$post->id} actualizada por el administrador.");
        $this->closeEditModal();
    }

    public function toggleStatus(int $postId): void
    {
        $post = Post::findOrFail($postId);
        $newStatus = ($post->status === 'active') ? 'hidden' : 'active';
        $post->update(['status' => $newStatus]);

        $msg = $newStatus === 'hidden' ? 'ocultada' : 'activada';
        session()->flash('success', "La publicación #{$post->id} ha sido {$msg}.");
    }

    public function deletePost(int $postId, CloudinaryService $cloudinaryService): void
    {
        $post = Post::findOrFail($postId);

        // Delete cloud media via CloudinaryService
        if (!empty($post->cloudinary_public_id)) {
            $cloudinaryService->deleteImage($post->cloudinary_public_id);
        }

        // Delete post from database
        $postTitle = $post->title;
        $post->delete();

        session()->flash('success', "Publicación '{$postTitle}' y su contenido multimedia en Cloudinary han sido eliminados permanentemente.");
    }

    public function render()
    {
        $query = Post::with(['user', 'business']);

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('title', 'like', '%' . $this->search . '%')
                  ->orWhere('description', 'like', '%' . $this->search . '%')
                  ->orWhereHas('user', function ($uq) {
                      $uq->where('name', 'like', '%' . $this->search . '%')
                        ->orWhere('email', 'like', '%' . $this->search . '%');
                  });
            });
        }

        if (!empty($this->categoryFilter)) {
            $query->where('category', $this->categoryFilter);
        }

        if (!empty($this->statusFilter)) {
            $query->where('status', $this->statusFilter);
        }

        $posts = $query->latest()->paginate(9);

        // Extract available categories dynamically or static options
        $categories = ['Plástico PET', 'Vidrio', 'Cartón / Papel', 'Metales / Aluminio', 'Aceite Usado', 'Electrónicos', 'Jabón Artesanal Saponificado'];

        return view('livewire.admin.post-management', [
            'posts' => $posts,
            'categories' => $categories
        ])->layout('layouts.app', ['title' => 'Moderación de Publicaciones - Admin EcoRecicla']);
    }
}
