<?php

return [
    'nav' => [
        'privacy' => 'Confidentialité',
        'terms' => 'Conditions',
    ],

    'updated' => 'Dernière mise à jour : :date',

    'consent' => 'En créant un compte, vous acceptez les [Conditions](:terms) et confirmez avoir lu la [Politique de confidentialité](:privacy).',

    'privacy' => [
        'title' => 'Politique de confidentialité',
        'intro' => 'Cette page explique quelles données Moodler (moodler.app) collecte, pourquoi il les utilise et quels sont vos droits.',
        'sections' => [
            [
                'heading' => 'Responsable du traitement',
                'blocks' => [
                    'Moodler est géré par :operator, responsable du traitement des données décrites ici. Pour toute question sur la confidentialité, ou pour exercer vos droits, écrivez à :email.',
                ],
            ],
            [
                'heading' => 'Les données que nous collectons',
                'blocks' => [
                    '- **Compte** : nom, adresse e-mail et mot de passe, que nous conservons chiffré. Si vous vous connectez avec Google, nous recevons votre nom, votre e-mail et un identifiant de compte, jamais votre mot de passe.
- **Ce que vous écrivez et choisissez** : la phrase qui décrit votre moment, les humeurs, genres et nuances choisis, les playlists que vous enregistrez et la liste des morceaux déjà proposés, pour ne pas vous les reproposer.
- **Abonnement** : l’état de votre abonnement et un identifiant client Paddle. Les données de paiement, comme la carte et l’adresse de facturation, sont gérées par Paddle et ne passent pas par nous.
- **Données techniques** : adresse IP et informations sur le navigateur, utilisées pour la sécurité et pour limiter les abus.',
                ],
            ],
            [
                'heading' => 'Pourquoi nous les utilisons',
                'blocks' => [
                    '- Pour vous permettre d’utiliser Moodler et de générer des playlists (exécution du contrat).
- Pour gérer votre compte et votre abonnement (exécution du contrat).
- Pour protéger le service contre les abus et les attaques, par exemple en limitant les demandes de chaque visiteur (intérêt légitime).
- Pour respecter nos obligations légales.',
                    'Nous ne vendons pas vos données, ne les utilisons pas pour la publicité et ne faisons pas de profilage.',
                ],
            ],
            [
                'heading' => 'Services tiers',
                'blocks' => [
                    'Pour fonctionner, Moodler s’appuie sur ces services, qui ne reçoivent que les données nécessaires :',
                    '- **OpenAI** reçoit votre phrase et vos choix pour proposer l’atmosphère et les morceaux. Il ne reçoit ni votre nom ni votre e-mail. [Politique de confidentialité d’OpenAI](https://openai.com/policies/privacy-policy/)
- **Spotify** sert à trouver les morceaux dans son catalogue et à les relier à Spotify. Il ne reçoit aucune donnée qui vous identifie. [Politique de confidentialité de Spotify](https://www.spotify.com/legal/privacy-policy/)
- **YouTube** : nous utilisons les YouTube API Services pour trouver la vidéo de chaque morceau et la lire dans le lecteur YouTube, qui ne se charge que lorsque vous appuyez sur lecture. En utilisant le lecteur, vous acceptez les [Conditions d’utilisation de YouTube](https://www.youtube.com/t/terms). La façon dont Google traite les données est décrite dans les [Règles de confidentialité de Google](https://policies.google.com/privacy).
- **Google**, si vous choisissez de vous connecter avec votre compte Google.
- **Paddle**, notre revendeur (merchant of record), qui gère les paiements, les factures et les remboursements. [Politique de confidentialité de Paddle](https://www.paddle.com/legal/privacy)
- **Notre hébergeur**, qui héberge le site et la base de données.',
                    'Certains de ces prestataires peuvent traiter des données hors de l’Union européenne. Dans ce cas, le transfert s’appuie sur les garanties prévues par le RGPD, comme les clauses contractuelles types.',
                ],
            ],
            [
                'heading' => 'Cookies',
                'blocks' => [
                    'Nous n’utilisons que des cookies techniques, nécessaires au fonctionnement du site :',
                    '- les cookies de session et de sécurité, qui vous gardent connecté et protègent les formulaires ;
- `locale`, qui mémorise la langue choisie pendant un an ;
- `moodler_guest`, qui mémorise pendant un an les morceaux déjà proposés aux visiteurs sans compte.',
                    'Nous n’utilisons aucun cookie d’analyse ni publicitaire. Lorsque vous lancez le lecteur, YouTube peut déposer ses propres cookies selon ses règles.',
                ],
            ],
            [
                'heading' => 'Durée de conservation',
                'blocks' => [
                    'Nous conservons les données de votre compte et vos playlists tant que votre compte existe. Si vous nous demandez de le supprimer, nous effaçons compte, playlists et historique sous 30 jours, sauf les données que la loi impose de conserver, comme les données fiscales tenues par Paddle. L’historique des visiteurs sans compte est lié au cookie `moodler_guest`.',
                ],
            ],
            [
                'heading' => 'Vos droits',
                'blocks' => [
                    'Vous pouvez à tout moment demander à accéder à vos données, à les rectifier, à les effacer, à en limiter le traitement, à vous y opposer ou à les recevoir dans un format portable, en écrivant à :email.',
                    'Si vous estimez que vos données sont mal traitées, vous pouvez introduire une réclamation auprès de l’autorité de contrôle de votre pays : en France, la [CNIL](https://www.cnil.fr).',
                ],
            ],
            [
                'heading' => 'Modifications',
                'blocks' => [
                    'Si nous modifions cette politique, nous mettons à jour la date en haut de la page. Pour les changements importants, nous prévenons par e-mail les personnes qui ont un compte.',
                ],
            ],
        ],
    ],

    'terms' => [
        'title' => 'Conditions d’utilisation',
        'intro' => 'Ces conditions régissent l’utilisation de Moodler (moodler.app), géré par :operator. En utilisant le site, vous les acceptez. Pour toute question, écrivez à :email.',
        'sections' => [
            [
                'heading' => 'Le service',
                'blocks' => [
                    'Moodler transforme la description d’un moment en playlist : une intelligence artificielle propose l’atmosphère et les morceaux, qui sont recherchés sur Spotify et lus dans le lecteur YouTube. Les suggestions sont générées automatiquement et ne sont pas toujours précises.',
                    'Moodler n’héberge pas de musique : les morceaux restent sur Spotify et YouTube, selon leurs conditions.',
                ],
            ],
            [
                'heading' => 'Compte',
                'blocks' => [
                    'Un compte est nécessaire pour enregistrer des playlists. Vous devez avoir au moins 14 ans, ou l’âge minimum prévu dans votre pays, fournir des informations exactes et protéger vos identifiants. Vous pouvez fermer votre compte à tout moment en écrivant à :email.',
                ],
            ],
            [
                'heading' => 'Abonnement',
                'blocks' => [
                    'Enregistrer et rouvrir des playlists nécessite un abonnement à :price, renouvelé automatiquement chaque mois jusqu’à ce que vous le résiliiez. Vous pouvez le résilier à tout moment depuis la page Compte : il reste actif jusqu’à la fin de la période déjà payée. Si nous changeons le prix, nous vous prévenons avant le renouvellement.',
                    'Les commandes sont gérées par Paddle.com, notre revendeur (merchant of record), qui s’occupe des paiements, des factures et de l’assistance sur les commandes. Les [conditions acheteur de Paddle](https://www.paddle.com/legal/checkout-buyer-terms) s’appliquent également à votre achat.',
                ],
            ],
            [
                'heading' => 'Remboursements',
                'blocks' => [
                    'Vous pouvez demander le remboursement d’un paiement dans les 14 jours, sans avoir à vous justifier, en écrivant à :email ou via [paddle.net](https://paddle.net). Le remboursement est effectué sur le même moyen de paiement.',
                ],
            ],
            [
                'heading' => 'Utilisation correcte',
                'blocks' => [
                    'N’utilisez pas Moodler à des fins illégales, n’essayez pas de contourner ses limites, n’y accédez pas de manière automatisée (bots, scraping) et ne le surchargez pas. Nous pouvons suspendre l’accès de quiconque le fait.',
                ],
            ],
            [
                'heading' => 'Contenus de tiers',
                'blocks' => [
                    'Les morceaux, pochettes et vidéos appartiennent à leurs titulaires et sont affichés via Spotify et YouTube. En utilisant le lecteur, vous acceptez les [Conditions d’utilisation de YouTube](https://www.youtube.com/t/terms).',
                ],
            ],
            [
                'heading' => 'Responsabilité',
                'blocks' => [
                    'Nous faisons notre possible pour que Moodler fonctionne bien, mais le service est fourni en l’état et peut connaître des interruptions, y compris à cause des services externes qu’il utilise. Dans les limites permises par la loi, nous ne sommes pas responsables des dommages indirects. Vos droits de consommateur ne sont pas affectés.',
                ],
            ],
            [
                'heading' => 'Modifications et droit applicable',
                'blocks' => [
                    'Nous pouvons mettre à jour ces conditions : pour les changements importants, nous prévenons par e-mail les personnes qui ont un compte. Le droit italien s’applique, sans préjudice des droits impératifs que votre pays de résidence reconnaît aux consommateurs.',
                ],
            ],
        ],
    ],
];
