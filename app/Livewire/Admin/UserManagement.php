<?php

namespace App\Livewire\Admin;

use App\Models\User;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserManagement extends Component
{
    use WithPagination;

    protected $paginationTheme = 'tailwind';

    public string $search = '';
    public string $roleFilter = '';
    public bool $showTrashed = false;

    // Form Modal states
    public bool $isCreateModalOpen = false;
    public bool $isEditModalOpen = false;

    // User Form fields
    public ?int $selectedUserId = null;
    public string $name = '';
    public string $email = '';
    public string $role = 'user';
    public string $password = '';
    public string $password_confirmation = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRoleFilter(): void
    {
        $this->resetPage();
    }

    public function updatingShowTrashed(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->resetValidation();
        $this->resetForm();
        $this->isCreateModalOpen = true;
    }

    public function closeCreateModal(): void
    {
        $this->isCreateModalOpen = false;
        $this->resetForm();
    }

    public function openEditModal(int $userId): void
    {
        $this->resetValidation();
        $user = User::withTrashed()->findOrFail($userId);
        
        $this->selectedUserId = $user->id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->role = $user->role;
        $this->password = '';
        $this->password_confirmation = '';

        $this->isEditModalOpen = true;
    }

    public function closeEditModal(): void
    {
        $this->isEditModalOpen = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->selectedUserId = null;
        $this->name = '';
        $this->email = '';
        $this->role = 'user';
        $this->password = '';
        $this->password_confirmation = '';
    }

    public function createUser(): void
    {
        $validated = $this->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'role' => 'required|in:admin,user,business',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique' => 'Este correo electrónico ya está registrado.',
            'role.in' => 'El rol seleccionado no es válido.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min' => 'La contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'password' => Hash::make($validated['password']),
        ]);

        session()->flash('success', 'Usuario registrado correctamente.');
        $this->closeCreateModal();
    }

    public function updateUser(): void
    {
        if (!$this->selectedUserId) return;

        $user = User::withTrashed()->findOrFail($this->selectedUserId);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'role' => 'required|in:admin,user,business',
        ];

        if (!empty($this->password)) {
            $rules['password'] = 'string|min:6|confirmed';
        }

        $validated = $this->validate($rules, [
            'name.required' => 'El nombre es obligatorio.',
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.unique' => 'Este correo ya pertenece a otro usuario.',
            'role.in' => 'El rol seleccionado no es válido.',
            'password.min' => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
        ];

        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        $user->update($data);

        session()->flash('success', "Usuario {$user->name} actualizado exitosamente.");
        $this->closeEditModal();
    }

    public function deleteUser(int $userId): void
    {
        $user = User::findOrFail($userId);

        if ($user->id === auth()->id()) {
            session()->flash('error', 'No puedes desactivar tu propia cuenta de administrador actual.');
            return;
        }

        $user->delete();
        session()->flash('success', "Usuario {$user->name} desactivado (Soft Delete) correctamente.");
    }

    public function restoreUser(int $userId): void
    {
        $user = User::onlyTrashed()->findOrFail($userId);
        $user->restore();
        session()->flash('success', "Usuario {$user->name} reactivado con éxito.");
    }

    public function render()
    {
        $query = User::query();

        if ($this->showTrashed) {
            $query->onlyTrashed();
        }

        if (!empty($this->search)) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if (!empty($this->roleFilter)) {
            $query->where('role', $this->roleFilter);
        }

        $users = $query->latest()->paginate(10);

        return view('livewire.admin.user-management', [
            'users' => $users
        ])->layout('layouts.app', ['title' => 'Gestión de Usuarios - Admin EcoRecicla']);
    }
}
