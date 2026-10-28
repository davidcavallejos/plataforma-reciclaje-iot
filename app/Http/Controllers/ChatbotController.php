<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\ChatbotHistorial;
use App\Services\ChatbotService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ChatbotController extends Controller
{
    public function responder(Request $request, ChatbotService $chatbot): JsonResponse
    {
        $datos = $request->validate([
            'pregunta' => ['required', 'string', 'max:500'],
        ], [
            'pregunta.required' => 'Escribe una pregunta.',
        ]);

        $resultado = $chatbot->responder($datos['pregunta'], $request->user());

        ChatbotHistorial::create([
            'user_id' => $request->user()?->id,
            'pregunta' => $datos['pregunta'],
            'respuesta' => $resultado['respuesta'],
            'fecha_hora' => now(),
        ]);

        return response()->json($resultado);
    }
}
