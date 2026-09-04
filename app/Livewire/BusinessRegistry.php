<?php

namespace App\Livewire;

use App\Models\Business;
use Livewire\Component;

class BusinessRegistry extends Component
{
    public bool $showRegisterModal = false;

    public string $business_name = '';
    public string $business_type = 'Centro de Acopio Multimaterial';
    public string $materials_handled = 'Plástico, Cartón, Aceite Vegetal, Vidrio';
    public string $nit_ruc = '';
    public string $phone_whatsapp = '+59170000000';
    public string $address = 'Av. Ecológica #100, Bolivia';
    public float $latitude = -16.500000;
    public float $longitude = -68.150000;
    public string $description = '';

    protected $rules = [
        'business_name' => 'required|string|max:150',
        'business_type' => 'required|string|max:50',
        'materials_handled' => 'required|string',
        'phone_whatsapp' => 'required|string|max:30',
        'address' => 'required|string',
        'latitude' => 'required|numeric',
        'longitude' => 'required|numeric',
        'description' => 'nullable|string',
    ];

    public function registerBusiness()
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $this->validate();

        Business::create([
            'user_id' => auth()->id(),
            'business_name' => e($this->business_name),
            'business_type' => e($this->business_type),
            'materials_handled' => e($this->materials_handled),
            'nit_ruc' => e($this->nit_ruc),
            'phone_whatsapp' => e($this->phone_whatsapp),
            'address' => e($this->address),
            'latitude' => $this->latitude,
            'longitude' => $this->longitude,
            'description' => e($this->description),
        ]);

        $this->reset(['business_name', 'description', 'showRegisterModal']);
        session()->flash('success', '¡Centro de acopio registrado exitosamente!');
    }

    public function render()
    {
        return view('livewire.business-registry', [
            'businesses' => Business::with('user')->latest()->get(),
        ])->layout('layouts.app');
    }
}
