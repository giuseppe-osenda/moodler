<?php

return [
    'nav' => [
        'privacy' => 'Privacidad',
        'terms' => 'Términos',
    ],

    'updated' => 'Última actualización: :date',

    'consent' => 'Al crear una cuenta aceptas los [Términos](:terms) y confirmas que has leído la [Política de privacidad](:privacy).',

    'privacy' => [
        'title' => 'Política de privacidad',
        'intro' => 'Esta página explica qué datos recoge Moodler (moodler.app), para qué los usa y cuáles son tus derechos.',
        'sections' => [
            [
                'heading' => 'Responsable del tratamiento',
                'blocks' => [
                    'Moodler lo gestiona :operator, responsable del tratamiento de los datos descritos aquí. Para cualquier pregunta sobre privacidad, o para ejercer tus derechos, escribe a :email.',
                ],
            ],
            [
                'heading' => 'Qué datos recogemos',
                'blocks' => [
                    '- **Cuenta**: nombre, dirección de email y contraseña, que guardamos cifrada. Si entras con Google recibimos tu nombre, tu email y un identificador de la cuenta, nunca la contraseña.
- **Lo que escribes y eliges**: la frase que describe tu momento, los moods, géneros y matices que eliges, las playlists que guardas y la lista de canciones ya propuestas, para no volver a proponértelas.
- **Suscripción**: el estado de la suscripción y un identificador de cliente de Paddle. Los datos de pago, como la tarjeta y la dirección de facturación, los gestiona Paddle y no pasan por nosotros.
- **Datos técnicos**: dirección IP e información del navegador, que usamos por seguridad y para limitar abusos.',
                ],
            ],
            [
                'heading' => 'Para qué los usamos',
                'blocks' => [
                    '- Para que puedas usar Moodler y generar playlists (ejecución del contrato).
- Para gestionar tu cuenta y tu suscripción (ejecución del contrato).
- Para proteger el servicio de abusos y ataques, por ejemplo limitando las peticiones de cada visitante (interés legítimo).
- Para cumplir nuestras obligaciones legales.',
                    'No vendemos tus datos, no los usamos para publicidad y no te perfilamos.',
                ],
            ],
            [
                'heading' => 'Servicios de terceros',
                'blocks' => [
                    'Para funcionar, Moodler se apoya en estos servicios, que solo reciben los datos necesarios:',
                    '- **OpenAI** recibe tu frase y tus elecciones para proponer la atmósfera y las canciones. No recibe tu nombre ni tu email. [Política de privacidad de OpenAI](https://openai.com/policies/privacy-policy/)
- **Spotify** sirve para encontrar las canciones en su catálogo y enlazarlas a Spotify. No recibe datos que te identifiquen. [Política de privacidad de Spotify](https://www.spotify.com/legal/privacy-policy/)
- **YouTube**: usamos los YouTube API Services para buscar el vídeo de cada canción y reproducirlo con el reproductor de YouTube, que solo se carga cuando pulsas play. Al usar el reproductor aceptas los [Términos de servicio de YouTube](https://www.youtube.com/t/terms). Cómo trata Google los datos se explica en la [Política de privacidad de Google](https://policies.google.com/privacy).
- **Google**, si eliges entrar con tu cuenta de Google.
- **Paddle**, nuestro revendedor (merchant of record), que gestiona pagos, facturas y reembolsos. [Política de privacidad de Paddle](https://www.paddle.com/legal/privacy)
- **El proveedor de hosting**, que aloja el sitio y la base de datos.',
                    'Algunos de estos proveedores pueden tratar datos fuera de la Unión Europea. En ese caso la transferencia se hace con las garantías previstas por el RGPD, como las cláusulas contractuales tipo.',
                ],
            ],
            [
                'heading' => 'Cookies',
                'blocks' => [
                    'Solo usamos cookies técnicas, necesarias para que el sitio funcione:',
                    '- las cookies de sesión y de seguridad, que mantienen tu sesión iniciada y protegen los formularios;
- `locale`, que recuerda durante un año el idioma elegido;
- `moodler_guest`, que recuerda durante un año las canciones ya propuestas a quien no tiene cuenta.',
                    'No usamos cookies de análisis ni publicitarias. Cuando inicias el reproductor, YouTube puede instalar sus propias cookies según sus políticas.',
                ],
            ],
            [
                'heading' => 'Cuánto tiempo los conservamos',
                'blocks' => [
                    'Conservamos los datos de la cuenta y las playlists mientras la cuenta exista. Si nos pides borrarla, eliminamos cuenta, playlists e historial en un plazo de 30 días, salvo los datos que la ley obliga a conservar, como los fiscales que guarda Paddle. El historial de quien no tiene cuenta está ligado a la cookie `moodler_guest`.',
                ],
            ],
            [
                'heading' => 'Tus derechos',
                'blocks' => [
                    'Puedes pedir en cualquier momento acceder a tus datos, corregirlos, borrarlos, limitar su tratamiento, oponerte a él o recibirlos en un formato portable, escribiendo a :email.',
                    'Si crees que tus datos se tratan de forma incorrecta puedes presentar una reclamación ante la autoridad de control de tu país: en España, la [Agencia Española de Protección de Datos](https://www.aepd.es).',
                ],
            ],
            [
                'heading' => 'Cambios',
                'blocks' => [
                    'Si cambiamos esta política actualizamos la fecha de arriba. Para los cambios importantes avisamos por email a quien tiene cuenta.',
                ],
            ],
        ],
    ],

    'terms' => [
        'title' => 'Términos de servicio',
        'intro' => 'Estos términos regulan el uso de Moodler (moodler.app), gestionado por :operator. Al usar el sitio los aceptas. Para cualquier pregunta escribe a :email.',
        'sections' => [
            [
                'heading' => 'El servicio',
                'blocks' => [
                    'Moodler convierte la descripción de un momento en una playlist: una inteligencia artificial propone la atmósfera y las canciones, que se buscan en Spotify y se reproducen con el reproductor de YouTube. Las sugerencias se generan automáticamente y no siempre son precisas.',
                    'Moodler no aloja música: las canciones siguen en Spotify y YouTube, bajo sus condiciones.',
                ],
            ],
            [
                'heading' => 'Cuenta',
                'blocks' => [
                    'Para guardar playlists necesitas una cuenta. Debes tener al menos 14 años, o la edad mínima prevista en tu país, dar datos veraces y custodiar tus credenciales. Puedes cerrar la cuenta cuando quieras escribiendo a :email.',
                ],
            ],
            [
                'heading' => 'Suscripción',
                'blocks' => [
                    'Guardar y volver a abrir playlists requiere una suscripción de :price, que se renueva automáticamente cada mes hasta que la canceles. Puedes cancelarla en cualquier momento desde la página Cuenta y sigue activa hasta el final del periodo ya pagado. Si cambiamos el precio te avisamos antes de la renovación.',
                    'Los pedidos los gestiona Paddle.com, nuestro revendedor (merchant of record), que se ocupa de pagos, facturas y asistencia sobre los pedidos. A la compra se aplican también los [términos para compradores de Paddle](https://www.paddle.com/legal/checkout-buyer-terms).',
                ],
            ],
            [
                'heading' => 'Reembolsos',
                'blocks' => [
                    'Puedes pedir el reembolso de un pago en un plazo de 14 días, sin tener que dar explicaciones, escribiendo a :email o a través de [paddle.net](https://paddle.net). El reembolso llega al mismo método de pago.',
                ],
            ],
            [
                'heading' => 'Uso correcto',
                'blocks' => [
                    'No uses Moodler con fines ilegales, no intentes saltarte sus límites, no accedas de forma automatizada (bots, scraping) y no lo sobrecargues. Podemos suspender el acceso a quien lo haga.',
                ],
            ],
            [
                'heading' => 'Contenidos de terceros',
                'blocks' => [
                    'Las canciones, portadas y vídeos pertenecen a sus titulares y se muestran a través de Spotify y YouTube. Al usar el reproductor aceptas los [Términos de servicio de YouTube](https://www.youtube.com/t/terms).',
                ],
            ],
            [
                'heading' => 'Responsabilidad',
                'blocks' => [
                    'Hacemos todo lo posible para que Moodler funcione bien, pero el servicio se ofrece tal cual y puede tener interrupciones, también por problemas de los servicios externos que usa. En la medida en que la ley lo permita, no respondemos de daños indirectos. Quedan a salvo los derechos que la ley reconoce a los consumidores.',
                ],
            ],
            [
                'heading' => 'Cambios y ley aplicable',
                'blocks' => [
                    'Podemos actualizar estos términos: para los cambios importantes avisamos por email a quien tiene cuenta. Se aplica la ley italiana, sin perjuicio de los derechos irrenunciables que el país en el que vives reconoce a los consumidores.',
                ],
            ],
        ],
    ],
];
