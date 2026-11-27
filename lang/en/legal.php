<?php

return [
    'nav' => [
        'privacy' => 'Privacy',
        'terms' => 'Terms',
    ],

    'updated' => 'Last updated: :date',

    'consent' => 'By creating an account you accept the [Terms](:terms) and confirm you have read the [Privacy policy](:privacy).',

    'privacy' => [
        'title' => 'Privacy policy',
        'intro' => 'This page explains what data Moodler (moodler.app) collects, why it uses it and what your rights are.',
        'sections' => [
            [
                'heading' => 'Who is responsible for your data',
                'blocks' => [
                    'Moodler is run by :operator, the controller of the data described here. For any privacy question, or to exercise your rights, write to :email.',
                ],
            ],
            [
                'heading' => 'What we collect',
                'blocks' => [
                    '- **Account**: your name, email address and password, which we store encrypted. If you sign in with Google we receive your name, email and an account identifier, never your password.
- **What you write and choose**: the phrase describing your moment, the moods, genres and fine-tuning you pick, the playlists you save and the songs already suggested to you, so we do not suggest them again.
- **Subscription**: your subscription status and a Paddle customer identifier. Payment details such as your card and billing address are handled by Paddle and never reach us.
- **Technical data**: your IP address and browser details, used for security and to limit abuse.',
                ],
            ],
            [
                'heading' => 'Why we use it',
                'blocks' => [
                    '- To let you use Moodler and generate playlists (performance of a contract).
- To manage your account and subscription (performance of a contract).
- To protect the service from abuse and attacks, for example by limiting how many requests each visitor can make (legitimate interest).
- To meet our legal obligations.',
                    'We do not sell your data, use it for advertising or profile you.',
                ],
            ],
            [
                'heading' => 'Third-party services',
                'blocks' => [
                    'Moodler relies on these services, which receive only the data they need:',
                    '- **OpenAI** receives your phrase and choices to suggest the atmosphere and songs. It does not receive your name or email. [OpenAI privacy policy](https://openai.com/policies/privacy-policy/)
- **Spotify** is used to find songs in its catalog and link them to Spotify. It receives no data that identifies you. [Spotify privacy policy](https://www.spotify.com/legal/privacy-policy/)
- **YouTube**: we use the YouTube API Services to find each song’s video and play it in the YouTube player, which loads only when you press play. By using the player you agree to the [YouTube Terms of Service](https://www.youtube.com/t/terms). How Google handles data is explained in the [Google Privacy Policy](https://policies.google.com/privacy).
- **Google**, if you choose to sign in with your Google account.
- **Paddle**, our reseller (merchant of record), which handles payments, invoices and refunds. [Paddle privacy policy](https://www.paddle.com/legal/privacy)
- **Our hosting provider**, which hosts the site and its database.',
                    'Some of these providers may process data outside the European Union. When they do, the transfer relies on the safeguards required by the GDPR, such as standard contractual clauses.',
                ],
            ],
            [
                'heading' => 'Cookies',
                'blocks' => [
                    'We only use technical cookies the site needs to work:',
                    '- session and security cookies, which keep you signed in and protect forms;
- `locale`, which remembers your language for a year;
- `moodler_guest`, which remembers for a year the songs already suggested to visitors without an account.',
                    'We use no analytics or advertising cookies. When you start the player, YouTube may set its own cookies under its policies.',
                ],
            ],
            [
                'heading' => 'How long we keep it',
                'blocks' => [
                    'We keep your account data and playlists for as long as your account exists. If you ask us to delete it, we remove your account, playlists and history within 30 days, except data the law requires us to keep, such as tax records held by Paddle. The history of visitors without an account is tied to the `moodler_guest` cookie.',
                ],
            ],
            [
                'heading' => 'Your rights',
                'blocks' => [
                    'You can ask at any time to access, correct or delete your data, restrict or object to its processing, or receive it in a portable format, by writing to :email.',
                    'If you think your data is being handled improperly you can complain to the data protection authority of your country: in Italy, the [Garante per la protezione dei dati personali](https://www.garanteprivacy.it).',
                ],
            ],
            [
                'heading' => 'Changes',
                'blocks' => [
                    'If we change this policy we update the date at the top. For important changes we email everyone who has an account.',
                ],
            ],
        ],
    ],

    'terms' => [
        'title' => 'Terms of service',
        'intro' => 'These terms govern the use of Moodler (moodler.app), run by :operator. By using the site you accept them. For any question, write to :email.',
        'sections' => [
            [
                'heading' => 'The service',
                'blocks' => [
                    'Moodler turns the description of a moment into a playlist: an artificial intelligence suggests the atmosphere and songs, which are looked up on Spotify and played in the YouTube player. Suggestions are generated automatically and are not always accurate.',
                    'Moodler does not host music: songs stay on Spotify and YouTube, under their terms.',
                ],
            ],
            [
                'heading' => 'Accounts',
                'blocks' => [
                    'You need an account to save playlists. You must be at least 14, or the minimum age in your country, give accurate details and keep your credentials safe. You can close your account at any time by writing to :email.',
                ],
            ],
            [
                'heading' => 'Subscription',
                'blocks' => [
                    'Saving and reopening playlists requires a subscription at :price, which renews automatically every month until you cancel it. You can cancel at any time from the Account page, and it stays active until the end of the period already paid. If we change the price we tell you before your next renewal.',
                    'Orders are handled by Paddle.com, our reseller (merchant of record), which takes care of payments, invoices and order support. The [Paddle buyer terms](https://www.paddle.com/legal/checkout-buyer-terms) also apply to your purchase.',
                ],
            ],
            [
                'heading' => 'Refunds',
                'blocks' => [
                    'You can ask for a refund of any payment within 14 days, without giving a reason, by writing to :email or through [paddle.net](https://paddle.net). The refund goes back to the same payment method.',
                ],
            ],
            [
                'heading' => 'Fair use',
                'blocks' => [
                    'Do not use Moodler for anything illegal, try to get around its limits, access it by automated means (bots, scraping) or overload it. We may suspend access for anyone who does.',
                ],
            ],
            [
                'heading' => 'Third-party content',
                'blocks' => [
                    'Songs, artwork and videos belong to their owners and are shown through Spotify and YouTube. By using the player you agree to the [YouTube Terms of Service](https://www.youtube.com/t/terms).',
                ],
            ],
            [
                'heading' => 'Liability',
                'blocks' => [
                    'We do our best to keep Moodler working well, but the service is provided as is and may be interrupted, including by problems with the outside services it relies on. To the extent the law allows, we are not liable for indirect damages. Your rights as a consumer are not affected.',
                ],
            ],
            [
                'heading' => 'Changes and governing law',
                'blocks' => [
                    'We may update these terms; for important changes we email everyone who has an account. Italian law applies, without prejudice to the mandatory consumer rights of the country you live in.',
                ],
            ],
        ],
    ],
];
