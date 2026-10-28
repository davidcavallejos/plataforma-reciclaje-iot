<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Perfil;
use App\Services\Esp32Service;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PerfilesController extends Controller
{
    public function index(Request $request): View
    {
        $perfiles = Perfil::with('user:id,nombre')
            ->withCount('lotes')
            ->orderByDesc('es_predeterminado')
            ->orderBy('nombre')
            ->get();

        $editando = null;
        if ($request->filled('editar')) {
            $editando = Perfil::find($request->integer('editar'));
        }

        return view('perfiles.index', [
            'perfiles' => $perfiles,
            'editando' => $editando,
            'componentes' => Esp32Service::COMPONENTES,
            'mapaPwm' => Perfil::MAPA_PWM,
            'pwmDefecto' => Perfil::PWM_POR_DEFECTO,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $datos = $request->validate($this->reglas(), $this->mensajes());

        if ($request->boolean('es_predeterminado')) {
            Perfil::where('user_id', $request->user()->id)->update(['es_predeterminado' => false]);
        }

        $perfil = Perfil::create([
            'user_id' => $request->user()->id,
            'nombre' => $datos['nombre'],
            'configuraciones_json' => $this->configuracion($datos),
            'es_predeterminado' => $request->boolean('es_predeterminado'),
        ]);

        return redirect()->route('perfiles.index')
            ->with('estado', "Perfil «{$perfil->nombre}» creado.");
    }

    public function update(Request $request, Perfil $perfil): RedirectResponse
    {
        $esPropietario = $perfil->user_id === $request->user()->id;
        abort_unless($esPropietario || $request->user()->esAdministrador(), 403, 'No puedes editar este perfil.');

        $datos = $request->validate($this->reglas($perfil), $this->mensajes());

        if ($request->boolean('es_predeterminado')) {
            Perfil::where('user_id', $perfil->user_id)
                ->whereKeyNot($perfil->id)
                ->update(['es_predeterminado' => false]);
        }

        $perfil->update([
            'nombre' => $datos['nombre'],
            'configuraciones_json' => $this->configuracion($datos),
            'es_predeterminado' => $request->boolean('es_predeterminado'),
        ]);

        return redirect()->route('perfiles.index')
            ->with('estado', "Perfil «{$perfil->nombre}» actualizado.");
    }

    public function destroy(Request $request, Perfil $perfil): RedirectResponse
    {
        $esPropietario = $perfil->user_id === $request->user()->id;
        abort_unless($esPropietario || $request->user()->esAdministrador(), 403, 'No puedes eliminar este perfil.');

        try {
            $nombre = $perfil->nombre;
            $perfil->delete();

            return redirect()->route('perfiles.index')->with('estado', "Perfil «{$nombre}» eliminado.");
        } catch (QueryException) {
            return redirect()->route('perfiles.index')->withErrors([
                'perfil' => 'No se puede eliminar el perfil porque está en uso.',
            ]);
        }
    }

    public function predeterminado(Request $request, Perfil $perfil): RedirectResponse
    {
        $esPropietario = $perfil->user_id === $request->user()->id;
        abort_unless($esPropietario || $request->user()->esAdministrador(), 403, 'No puedes modificar este perfil.');

        Perfil::where('user_id', $perfil->user_id)->update(['es_predeterminado' => false]);
        $perfil->update(['es_predeterminado' => true]);

        return redirect()->route('perfiles.index')
            ->with('estado', "«{$perfil->nombre}» es ahora el perfil predeterminado.");
    }

    /**
     * @return array<string, mixed>
     */
    private function reglas(?Perfil $perfil = null): array
    {
        $claves = array_keys(Esp32Service::COMPONENTES);

        return [
            'nombre' => [
                'required', 'string', 'min:3', 'max:120',
                Rule::unique('perfiles', 'nombre')
                    ->where('user_id', auth()->id())
                    ->ignore($perfil?->id),
            ],
            'pasos' => ['required', 'array', 'min:1', 'max:12'],
            'pasos.*.componente' => ['required', 'string', Rule::in($claves)],
            'pasos.*.tiempo_min' => ['required', 'numeric', 'min:0.1', 'max:240'],
            'pasos.*.pwm' => ['required', 'integer', 'between:0,255'],
            'pasos.*.tiempo_on_s' => ['nullable', 'integer', 'between:1,3600'],
            'pasos.*.tiempo_off_s' => ['nullable', 'integer', 'between:1,3600'],
            'pasos.*.repeticiones' => ['nullable', 'integer', 'between:1,100'],
            'es_predeterminado' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    private function mensajes(): array
    {
        return [
            'nombre.unique' => 'Ya tienes un perfil con ese nombre.',
            'pasos.required' => 'Añade al menos un componente a la secuencia.',
            'pasos.min' => 'Añade al menos un componente a la secuencia.',
        ];
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, mixed>
     */
    private function configuracion(array $datos): array
    {
        $pasos = [];
        $pwm = Perfil::PWM_POR_DEFECTO;

        foreach ($datos['pasos'] as $paso) {
            $componente = (string) $paso['componente'];
            $normalizado = [
                'componente' => $componente,
                'tiempo_min' => round((float) $paso['tiempo_min'], 2),
                'pwm' => (int) $paso['pwm'],
            ];

            if ($componente === 'OIL') {
                $normalizado['tiempo_on_s'] = (int) ($paso['tiempo_on_s'] ?? 5);
                $normalizado['tiempo_off_s'] = (int) ($paso['tiempo_off_s'] ?? 10);
                $normalizado['repeticiones'] = (int) ($paso['repeticiones'] ?? 6);
            }

            $clavePwm = Perfil::MAPA_PWM[$componente] ?? null;
            if ($clavePwm !== null) {
                $pwm[$clavePwm] = (int) $paso['pwm'];
            }

            $pasos[] = $normalizado;
        }

        return [
            'pwm' => $pwm,
            'pasos' => $pasos,
        ];
    }
}
