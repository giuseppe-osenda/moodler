<?php

return [
    'nav' => [
        'privacy' => 'Datenschutz',
        'terms' => 'Nutzungsbedingungen',
    ],

    'updated' => 'Zuletzt aktualisiert: :date',

    'consent' => 'Mit dem Erstellen eines Kontos akzeptierst du die [Nutzungsbedingungen](:terms) und bestätigst, dass du die [Datenschutzerklärung](:privacy) gelesen hast.',

    'privacy' => [
        'title' => 'Datenschutzerklärung',
        'intro' => 'Diese Seite erklärt, welche Daten Moodler (moodler.app) erhebt, wofür sie verwendet werden und welche Rechte du hast.',
        'sections' => [
            [
                'heading' => 'Verantwortlicher',
                'blocks' => [
                    'Moodler wird von :operator betrieben, dem Verantwortlichen für die hier beschriebenen Daten. Bei Fragen zum Datenschutz oder um deine Rechte auszuüben, schreib an :email.',
                ],
            ],
            [
                'heading' => 'Welche Daten wir erheben',
                'blocks' => [
                    '- **Konto**: Name, E-Mail-Adresse und Passwort, das wir verschlüsselt speichern. Wenn du dich mit Google anmeldest, erhalten wir deinen Namen, deine E-Mail-Adresse und eine Konto-ID, aber nie dein Passwort.
- **Was du schreibst und wählst**: der Satz, der deinen Moment beschreibt, die gewählten Stimmungen, Genres und Feinheiten, die Playlists, die du speicherst, und die Liste der bereits vorgeschlagenen Songs, damit wir sie dir nicht noch einmal vorschlagen.
- **Abo**: der Status deines Abos und eine Kunden-ID von Paddle. Zahlungsdaten wie Karte und Rechnungsadresse verwaltet Paddle; sie laufen nicht über uns.
- **Technische Daten**: IP-Adresse und Browserinformationen, die wir für die Sicherheit und zur Begrenzung von Missbrauch verwenden.',
                ],
            ],
            [
                'heading' => 'Wofür wir sie verwenden',
                'blocks' => [
                    '- Damit du Moodler nutzen und Playlists erstellen kannst (Vertragserfüllung).
- Um dein Konto und dein Abo zu verwalten (Vertragserfüllung).
- Um den Dienst vor Missbrauch und Angriffen zu schützen, zum Beispiel indem wir die Anfragen pro Besucher begrenzen (berechtigtes Interesse).
- Um gesetzliche Pflichten zu erfüllen.',
                    'Wir verkaufen deine Daten nicht, nutzen sie nicht für Werbung und erstellen kein Profil von dir.',
                ],
            ],
            [
                'heading' => 'Dienste Dritter',
                'blocks' => [
                    'Moodler nutzt diese Dienste, die nur die jeweils nötigen Daten erhalten:',
                    '- **OpenAI** erhält deinen Satz und deine Auswahl, um Stimmung und Songs vorzuschlagen. Deinen Namen und deine E-Mail-Adresse erhält OpenAI nicht. [Datenschutzerklärung von OpenAI](https://openai.com/policies/privacy-policy/)
- **Spotify** dient dazu, die Songs im Katalog zu finden und mit Spotify zu verlinken. Spotify erhält keine Daten, die dich identifizieren. [Datenschutzerklärung von Spotify](https://www.spotify.com/legal/privacy-policy/)
- **YouTube**: Wir nutzen die YouTube API Services, um das Video zu jedem Song zu finden und im YouTube-Player abzuspielen, der erst geladen wird, wenn du auf Play drückst. Mit der Nutzung des Players akzeptierst du die [Nutzungsbedingungen von YouTube](https://www.youtube.com/t/terms). Wie Google Daten verarbeitet, steht in der [Datenschutzerklärung von Google](https://policies.google.com/privacy).
- **Google**, wenn du dich mit deinem Google-Konto anmeldest.
- **Paddle**, unser Wiederverkäufer (Merchant of Record), der Zahlungen, Rechnungen und Erstattungen abwickelt. [Datenschutzerklärung von Paddle](https://www.paddle.com/legal/privacy)
- **Unser Hosting-Anbieter**, der die Website und die Datenbank betreibt.',
                    'Einige dieser Anbieter verarbeiten Daten möglicherweise außerhalb der Europäischen Union. In diesem Fall erfolgt die Übermittlung mit den von der DSGVO vorgesehenen Garantien, etwa Standardvertragsklauseln.',
                ],
            ],
            [
                'heading' => 'Cookies',
                'blocks' => [
                    'Wir verwenden nur technisch notwendige Cookies:',
                    '- Sitzungs- und Sicherheits-Cookies, die dich angemeldet halten und Formulare schützen;
- `locale`, das sich deine Sprache ein Jahr lang merkt;
- `moodler_guest`, das sich bei Besuchern ohne Konto ein Jahr lang die bereits vorgeschlagenen Songs merkt.',
                    'Wir verwenden keine Analyse- oder Werbe-Cookies. Wenn du den Player startest, kann YouTube nach eigenen Regeln Cookies setzen.',
                ],
            ],
            [
                'heading' => 'Wie lange wir Daten speichern',
                'blocks' => [
                    'Wir speichern deine Kontodaten und Playlists, solange dein Konto besteht. Wenn du die Löschung verlangst, entfernen wir Konto, Playlists und Verlauf innerhalb von 30 Tagen, außer Daten, die wir gesetzlich aufbewahren müssen, etwa steuerliche Daten bei Paddle. Der Verlauf von Besuchern ohne Konto ist an das Cookie `moodler_guest` gebunden.',
                ],
            ],
            [
                'heading' => 'Deine Rechte',
                'blocks' => [
                    'Du kannst jederzeit Auskunft über deine Daten verlangen, sie berichtigen oder löschen lassen, die Verarbeitung einschränken, ihr widersprechen oder deine Daten in einem übertragbaren Format erhalten. Schreib dazu an :email.',
                    'Wenn du meinst, dass deine Daten nicht korrekt verarbeitet werden, kannst du dich bei der Datenschutz-Aufsichtsbehörde deines Landes beschweren, in Deutschland bei der Behörde deines Bundeslandes.',
                ],
            ],
            [
                'heading' => 'Änderungen',
                'blocks' => [
                    'Wenn wir diese Erklärung ändern, aktualisieren wir das Datum oben. Über wichtige Änderungen informieren wir alle mit Konto per E-Mail.',
                ],
            ],
        ],
    ],

    'terms' => [
        'title' => 'Nutzungsbedingungen',
        'intro' => 'Diese Bedingungen regeln die Nutzung von Moodler (moodler.app), betrieben von :operator. Mit der Nutzung der Website akzeptierst du sie. Bei Fragen schreib an :email.',
        'sections' => [
            [
                'heading' => 'Der Dienst',
                'blocks' => [
                    'Moodler verwandelt die Beschreibung eines Moments in eine Playlist: Eine künstliche Intelligenz schlägt Stimmung und Songs vor, die auf Spotify gesucht und im YouTube-Player abgespielt werden. Die Vorschläge werden automatisch erstellt und sind nicht immer treffend.',
                    'Moodler hostet keine Musik: Die Songs bleiben auf Spotify und YouTube, zu deren Bedingungen.',
                ],
            ],
            [
                'heading' => 'Konto',
                'blocks' => [
                    'Zum Speichern von Playlists brauchst du ein Konto. Du musst mindestens 14 Jahre alt sein, oder das in deinem Land vorgeschriebene Mindestalter haben, wahre Angaben machen und deine Zugangsdaten schützen. Du kannst dein Konto jederzeit schließen, indem du an :email schreibst.',
                ],
            ],
            [
                'heading' => 'Abo',
                'blocks' => [
                    'Zum Speichern und erneuten Öffnen von Playlists brauchst du ein Abo für :price, das sich jeden Monat automatisch verlängert, bis du es kündigst. Du kannst jederzeit auf der Kontoseite kündigen; das Abo bleibt bis zum Ende des bezahlten Zeitraums aktiv. Wenn wir den Preis ändern, informieren wir dich vor der Verlängerung.',
                    'Bestellungen wickelt Paddle.com ab, unser Wiederverkäufer (Merchant of Record), der sich um Zahlungen, Rechnungen und Support zu Bestellungen kümmert. Für den Kauf gelten außerdem die [Käuferbedingungen von Paddle](https://www.paddle.com/legal/checkout-buyer-terms).',
                ],
            ],
            [
                'heading' => 'Erstattungen',
                'blocks' => [
                    'Du kannst innerhalb von 14 Tagen ohne Angabe von Gründen die Erstattung einer Zahlung verlangen, per E-Mail an :email oder über [paddle.net](https://paddle.net). Die Erstattung geht an dieselbe Zahlungsmethode.',
                ],
            ],
            [
                'heading' => 'Faire Nutzung',
                'blocks' => [
                    'Nutze Moodler nicht für illegale Zwecke, versuche nicht, die Grenzen zu umgehen, greife nicht automatisiert darauf zu (Bots, Scraping) und überlaste den Dienst nicht. Wir können den Zugang für alle sperren, die das tun.',
                ],
            ],
            [
                'heading' => 'Inhalte Dritter',
                'blocks' => [
                    'Songs, Cover und Videos gehören ihren jeweiligen Rechteinhabern und werden über Spotify und YouTube angezeigt. Mit der Nutzung des Players akzeptierst du die [Nutzungsbedingungen von YouTube](https://www.youtube.com/t/terms).',
                ],
            ],
            [
                'heading' => 'Haftung',
                'blocks' => [
                    'Wir tun unser Bestes, damit Moodler gut funktioniert, aber der Dienst wird so bereitgestellt, wie er ist, und kann unterbrochen werden, auch durch Probleme der externen Dienste, die er nutzt. Soweit gesetzlich zulässig, haften wir nicht für indirekte Schäden. Deine Rechte als Verbraucher bleiben unberührt.',
                ],
            ],
            [
                'heading' => 'Änderungen und anwendbares Recht',
                'blocks' => [
                    'Wir können diese Bedingungen aktualisieren; über wichtige Änderungen informieren wir alle mit Konto per E-Mail. Es gilt italienisches Recht, unbeschadet der zwingenden Verbraucherrechte des Landes, in dem du lebst.',
                ],
            ],
        ],
    ],
];
