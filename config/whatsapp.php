<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Código de país por defecto
    |--------------------------------------------------------------------------
    | Se antepone a los números nacionales al armar el enlace wa.me. 54 = Argentina.
    | El número final siempre es editable en la UI antes de abrir WhatsApp.
    */
    'default_country' => env('WHATSAPP_COUNTRY', '54'),

    /*
    |--------------------------------------------------------------------------
    | Plantillas de mensaje
    |--------------------------------------------------------------------------
    | Variables disponibles: {cliente} {codigo} {email} {link} {empresa} {agente}.
    | El agente puede editar el texto antes de enviarlo. Los emojis son contenido
    | del mensaje de WhatsApp (no iconografía de interfaz).
    */
    'templates' => [
        [
            'key' => 'identificacion',
            'label' => 'Identificación',
            'icon' => 'badge-check',
            'text' => "Hola {cliente}, te escribimos de {empresa} por tu solicitud *{codigo}*.\n\n".
                "Podés seguir tu caso en {link}, ingresando ese código y tu email registrado ({email}).\n\n".
                'Si preferís, respondé por acá y seguimos la conversación. ¡Quedamos atentos!',
        ],
        [
            'key' => 'seguimiento',
            'label' => 'Seguimiento',
            'icon' => 'message-circle',
            'text' => "Hola {cliente}, te paso una novedad sobre tu ticket *{codigo}*:\n\n".
                "[escribí la actualización acá]\n\n".
                'Cualquier cosa respondé por acá o seguilo en {link}.',
        ],
        [
            'key' => 'resuelto',
            'label' => 'Resuelto',
            'icon' => 'circle-check-big',
            'text' => "Hola {cliente}, buenas noticias: marcamos tu ticket *{codigo}* como resuelto.\n\n".
                'Si necesitás algo más, respondé por acá o entrá a {link}. ¡Gracias por escribirnos!',
        ],
    ],
];
