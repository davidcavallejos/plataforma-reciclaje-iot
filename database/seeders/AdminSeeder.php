<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Business;
use App\Models\Post;
use App\Models\MachineTelemetry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Admin account per specification: admin / admin 12346789
        $admin = User::updateOrCreate(
            ['email' => 'admin'],
            [
                'name' => 'Administrador Sistema IoT',
                'password' => Hash::make('admin 12346789'),
                'role' => 'admin',
                'avatar_url' => 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=300&q=80',
            ]
        );

        // 2. Demo normal user account
        $user = User::updateOrCreate(
            ['email' => 'eco_user@reciclaje.org'],
            [
                'name' => 'María Recicladora',
                'password' => Hash::make('12345678'),
                'role' => 'user',
                'avatar_url' => 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&w=300&q=80',
            ]
        );

        // 3. Demo Recycling Center Business
        $business = Business::create([
            'user_id' => $admin->id,
            'business_name' => 'EcoCentro Verde Bolivia',
            'business_type' => 'Centro de Acopio Multimaterial',
            'materials_handled' => 'Plástico, Aceite Usado, Vidrio, Metal, Electrónicos',
            'nit_ruc' => '1029384756',
            'phone_whatsapp' => '+59171234567',
            'address' => 'Av. Ecológica #450, Zona Central',
            'latitude' => -16.500000,
            'longitude' => -68.150000,
            'description' => 'Centro especializado en acopio de aceites vegetales usados para conversión a jabón biodegradables y reciclaje industrial de plásticos.',
        ]);

        // 4. Demo Posts
        Post::create([
            'user_id' => $user->id,
            'business_id' => null,
            'title' => 'Lote de 20 Litros de Aceite Usado de Cocina',
            'description' => 'Aceite vegetal usado filtrado listo para acopio o proceso de saponificación.',
            'category' => 'Aceite',
            'quantity' => '20 Litros',
            'price' => 15.00,
            'is_donation' => false,
            'address' => 'Zona Sur, Calle 15 #120',
            'latitude' => -16.520000,
            'longitude' => -68.110000,
            'cloudinary_public_id' => 'oil_batch_01',
            'image_url' => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?auto=format&fit=crop&w=800&q=80',
            'status' => 'activo',
        ]);

        Post::create([
            'user_id' => $admin->id,
            'business_id' => $business->id,
            'title' => 'Acopio Masivo de Botellas PET y Plástico Duro',
            'description' => 'Recibimos botellas transparentes y tapitas. Pagamos por kilo o cambio por productos reciclados.',
            'category' => 'Plástico',
            'quantity' => '500 kg',
            'price' => 0.00,
            'is_donation' => true,
            'address' => 'Av. Ecológica #450',
            'latitude' => -16.500000,
            'longitude' => -68.150000,
            'cloudinary_public_id' => 'plastic_recycling_01',
            'image_url' => 'https://images.unsplash.com/photo-1530587191325-3db32d826c18?auto=format&fit=crop&w=800&q=80',
            'status' => 'activo',
        ]);

        Post::create([
            'user_id' => $user->id,
            'business_id' => null,
            'title' => 'Cajas de Cartón Ondulado y Periódico Antiguo',
            'description' => 'Ideal para embalaje o empresas papeleras. Aproximadamente 80 kilos limpios.',
            'category' => 'Cartón',
            'quantity' => '80 kg',
            'price' => 5.00,
            'is_donation' => false,
            'address' => 'Sopocachi, Calle Belisario Salinas',
            'latitude' => -16.510000,
            'longitude' => -68.130000,
            'cloudinary_public_id' => 'cardboard_batch_01',
            'image_url' => 'https://images.unsplash.com/photo-1589939705384-5185137a7f0f?auto=format&fit=crop&w=800&q=80',
            'status' => 'activo',
        ]);

        // 5. Demo Telemetry Record for Soap Machine
        MachineTelemetry::create([
            'oil_weight_grams' => 2500.00,
            'oil_volume_liters' => 2.71,
            'temperature_celsius' => 58.50,
            'agitator_rpm' => 320,
            'heater_status' => true,
            'pump_status' => false,
            'trace_detected' => false,
        ]);
    }
}
