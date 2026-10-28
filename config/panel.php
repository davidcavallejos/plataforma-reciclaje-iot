<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Pestañas del panel
    |--------------------------------------------------------------------------
    |
    | `ruta`    nombre de ruta de Laravel.
    | `permiso` null (todos los roles), controlar, reportes o usuarios.
    | `icono`   emoji para la navegación.
    |
    | Las pestañas cuya ruta todavía no exista no se muestran, así la navegación
    | nunca apunta a enlaces rotos.
    |
    */

    'pestanas' => [
        [
            'ruta' => 'dashboard',
            'titulo' => 'Inicio',
            'icono' => '🏠',
            'categoria' => 'Principal',
            'permiso' => null,
        ],
        [
            'ruta' => 'maquina.index',
            'titulo' => 'Control de la máquina',
            'icono' => '⚙️',
            'categoria' => 'Producción y Control',
            'permiso' => 'controlar',
        ],
        [
            'ruta' => 'recetas.index',
            'titulo' => 'Formulación y recetas',
            'icono' => '🧪',
            'categoria' => 'Producción y Control',
            'permiso' => 'controlar',
        ],
        [
            'ruta' => 'perfiles.index',
            'titulo' => 'Perfiles de funcionamiento',
            'icono' => '🎚️',
            'categoria' => 'Producción y Control',
            'permiso' => 'controlar',
        ],
        [
            'ruta' => 'lotes.index',
            'titulo' => 'Lotes y pedidos',
            'icono' => '📦',
            'categoria' => 'Producción y Control',
            'permiso' => 'controlar',
        ],
        [
            'ruta' => 'reportes.index',
            'titulo' => 'Reportes e informes',
            'icono' => '📊',
            'categoria' => 'Informes y Registros',
            'permiso' => 'reportes',
        ],
        [
            'ruta' => 'proyecto.index',
            'titulo' => 'Proyecto y registros',
            'icono' => '📚',
            'categoria' => 'Informes y Registros',
            'permiso' => null,
        ],
        [
            'ruta' => 'usuarios.index',
            'titulo' => 'Gestión de usuarios',
            'icono' => '👥',
            'categoria' => 'Administración y Sistema',
            'permiso' => 'usuarios',
        ],
        [
            'ruta' => 'noticias.index',
            'titulo' => 'Noticias (Tablón)',
            'icono' => '📰',
            'categoria' => 'Administración y Sistema',
            'permiso' => 'usuarios',
        ],
        [
            'ruta' => 'perfil.edit',
            'titulo' => 'Mi perfil',
            'icono' => '👤',
            'categoria' => 'Principal',
            'permiso' => null,
        ],
        [
            'ruta' => 'desarrolladores',
            'titulo' => 'Desarrolladores',
            'icono' => '💻',
            'categoria' => 'Administración y Sistema',
            'permiso' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Formulación
    |--------------------------------------------------------------------------
    |
    | Factores proporcionales de la receta: por cada unidad de aceite, cuánta
    | agua y cuánta sosa lleva la mezcla. Se usan para calcular el resto de
    | ingredientes cuando el operario indica el peso de uno solo.
    |
    */

    'formulacion' => [
        'aceite' => (float) env('FORMULA_ACEITE', 1.0),
        'agua' => (float) env('FORMULA_AGUA', 0.38),
        'sosa' => (float) env('FORMULA_SOSA', 0.14),
    ],

    /*
    |--------------------------------------------------------------------------
    | Aceites y grasas saponificables (Índices SAP NaOH en g/g)
    |--------------------------------------------------------------------------
    |
    | Valores de saponificación de referencia basados en literatura técnica
    | confiable (SoapCalc, tablas de saponificación reconocidas y The Soapmaker's
    | Companion). KOH = NaOH × 1.4025.
    |
    */
    'aceites' => [
        'oliva' => ['nombre' => 'Oliva', 'sap_naoh' => 0.134],
        'coco' => ['nombre' => 'Coco', 'sap_naoh' => 0.183],
        'palma' => ['nombre' => 'Palma', 'sap_naoh' => 0.141],
        'girasol' => ['nombre' => 'Girasol', 'sap_naoh' => 0.134],
        'soya' => ['nombre' => 'Soya', 'sap_naoh' => 0.135],
        'ricino' => ['nombre' => 'Ricino', 'sap_naoh' => 0.128],
        'canola' => ['nombre' => 'Canola (Colza)', 'sap_naoh' => 0.124],
        'aguacate' => ['nombre' => 'Aguacate (Palta)', 'sap_naoh' => 0.133],
        'almendra' => ['nombre' => 'Almendra dulce', 'sap_naoh' => 0.136],
        'karite' => ['nombre' => 'Manteca de Karité', 'sap_naoh' => 0.128],
        'cacao' => ['nombre' => 'Manteca de Cacao', 'sap_naoh' => 0.137],
        'maiz' => ['nombre' => 'Maíz', 'sap_naoh' => 0.136],
        'sesamo' => ['nombre' => 'Sésamo (Ajonjolí)', 'sap_naoh' => 0.133],
        'arroz' => ['nombre' => 'Salvado de Arroz', 'sap_naoh' => 0.128],
        'lino' => ['nombre' => 'Linaza (Lino)', 'sap_naoh' => 0.135],
        'pepita_uva' => ['nombre' => 'Pepita de Uva', 'sap_naoh' => 0.129],
        'sebo_vacuno' => ['nombre' => 'Sebo vacuno', 'sap_naoh' => 0.140],
        'manteca_cerdo' => ['nombre' => 'Manteca de Cerdo', 'sap_naoh' => 0.138],
        'reciclado' => ['nombre' => 'Aceite reciclado (cocina filtrado)', 'sap_naoh' => 0.138],
    ],

    'avatares' => [
        'jabon' => '🧼',
        'planta' => '🌿',
        'engranaje' => '⚙️',
        'gota' => '💧',
        'laboratorio' => '🧪',
        'estrella' => '⭐',
        'sol' => '☀️',
        'hoja' => '🍃',
    ],

];
