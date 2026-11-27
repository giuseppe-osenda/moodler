<?php

return [
    'nav' => [
        'privacy' => 'Privacy',
        'terms' => 'Termini',
    ],

    'updated' => 'Ultimo aggiornamento: :date',

    'consent' => 'Creando un account accetti i [Termini](:terms) e confermi di aver letto l’[Informativa privacy](:privacy).',

    'privacy' => [
        'title' => 'Informativa privacy',
        'intro' => 'Questa pagina spiega quali dati raccoglie Moodler (moodler.app), perché li usa e quali sono i tuoi diritti.',
        'sections' => [
            [
                'heading' => 'Titolare del trattamento',
                'blocks' => [
                    'Moodler è gestito da :operator, titolare del trattamento dei dati descritti qui. Per qualsiasi domanda sulla privacy, o per esercitare i tuoi diritti, scrivi a :email.',
                ],
            ],
            [
                'heading' => 'Quali dati raccogliamo',
                'blocks' => [
                    '- **Account**: nome, indirizzo email e password, che conserviamo cifrata. Se accedi con Google riceviamo il tuo nome, l’email e un identificativo dell’account, mai la password.
- **Quello che scrivi e scegli**: la frase che descrive il tuo momento, mood, generi e sfumature, le playlist che salvi e l’elenco dei brani già proposti, per non riproporteli.
- **Abbonamento**: lo stato dell’abbonamento e un identificativo cliente di Paddle. I dati di pagamento, come carta e indirizzo di fatturazione, li gestisce Paddle e non passano da noi.
- **Dati tecnici**: indirizzo IP e informazioni sul browser, che usiamo per la sicurezza e per limitare gli abusi.',
                ],
            ],
            [
                'heading' => 'Perché li usiamo',
                'blocks' => [
                    '- Per farti usare Moodler e generare le playlist (esecuzione del contratto).
- Per gestire il tuo account e l’abbonamento (esecuzione del contratto).
- Per proteggere il servizio da abusi e attacchi, ad esempio limitando le richieste di ogni visitatore (legittimo interesse).
- Per rispettare gli obblighi di legge.',
                    'Non vendiamo i tuoi dati, non li usiamo per la pubblicità e non ti profiliamo.',
                ],
            ],
            [
                'heading' => 'Servizi di terze parti',
                'blocks' => [
                    'Per funzionare Moodler si appoggia a questi servizi, che ricevono solo i dati necessari:',
                    '- **OpenAI** riceve la tua frase e le tue scelte per proporre l’atmosfera e i brani. Non riceve il tuo nome né la tua email. [Informativa di OpenAI](https://openai.com/policies/privacy-policy/)
- **Spotify** serve a trovare i brani nel catalogo e a collegarli a Spotify. Non riceve dati che ti identificano. [Informativa di Spotify](https://www.spotify.com/legal/privacy-policy/)
- **YouTube**: usiamo gli API Services di YouTube per cercare il video di ogni brano e riprodurlo con il lettore di YouTube, che si carica solo quando premi play. Usando il lettore accetti i [Termini di servizio di YouTube](https://www.youtube.com/t/terms). Come Google tratta i dati è spiegato nelle [Norme sulla privacy di Google](https://policies.google.com/privacy).
- **Google**, se scegli di accedere con il tuo account Google.
- **Paddle**, il nostro rivenditore (merchant of record), che gestisce pagamenti, fatture e rimborsi. [Informativa di Paddle](https://www.paddle.com/legal/privacy)
- **Il fornitore di hosting**, che ospita il sito e il database.',
                    'Alcuni di questi fornitori possono trattare dati fuori dall’Unione europea. In quel caso il trasferimento avviene con le garanzie previste dal GDPR, come le clausole contrattuali standard.',
                ],
            ],
            [
                'heading' => 'Cookie',
                'blocks' => [
                    'Usiamo solo cookie tecnici, necessari al funzionamento del sito:',
                    '- i cookie di sessione e di sicurezza, che ti tengono collegato e proteggono i moduli;
- `locale`, che ricorda la lingua scelta per un anno;
- `moodler_guest`, che per chi non ha un account ricorda per un anno i brani già proposti.',
                    'Non usiamo cookie di analisi né pubblicitari. Quando avvii il lettore, YouTube può impostare i propri cookie secondo le sue norme.',
                ],
            ],
            [
                'heading' => 'Per quanto tempo li conserviamo',
                'blocks' => [
                    'Conserviamo i dati dell’account e le playlist finché l’account esiste. Se ci chiedi di cancellarlo, eliminiamo account, playlist e storico entro 30 giorni, tranne i dati che la legge impone di conservare, come quelli fiscali tenuti da Paddle. Lo storico di chi non ha un account è legato al cookie `moodler_guest`.',
                ],
            ],
            [
                'heading' => 'I tuoi diritti',
                'blocks' => [
                    'Puoi chiedere in qualsiasi momento di accedere ai tuoi dati, correggerli, cancellarli, limitarne il trattamento, opporti al trattamento o riceverli in un formato portabile, scrivendo a :email.',
                    'Se pensi che i tuoi dati siano trattati in modo scorretto puoi presentare un reclamo all’autorità di controllo del tuo paese: in Italia è il [Garante per la protezione dei dati personali](https://www.garanteprivacy.it).',
                ],
            ],
            [
                'heading' => 'Modifiche',
                'blocks' => [
                    'Se cambiamo questa informativa aggiorniamo la data in alto. Per le modifiche importanti avvisiamo via email chi ha un account.',
                ],
            ],
        ],
    ],

    'terms' => [
        'title' => 'Termini di servizio',
        'intro' => 'Questi termini regolano l’uso di Moodler (moodler.app), gestito da :operator. Usando il sito li accetti. Per qualsiasi domanda scrivi a :email.',
        'sections' => [
            [
                'heading' => 'Il servizio',
                'blocks' => [
                    'Moodler trasforma la descrizione di un momento in una playlist: un’intelligenza artificiale propone l’atmosfera e i brani, che vengono cercati su Spotify e riprodotti con il lettore di YouTube. I suggerimenti sono generati automaticamente e non sono sempre precisi.',
                    'Moodler non ospita musica: i brani restano su Spotify e YouTube, alle loro condizioni.',
                ],
            ],
            [
                'heading' => 'Account',
                'blocks' => [
                    'Per salvare le playlist serve un account. Devi avere almeno 14 anni, o l’età minima prevista nel tuo paese, fornire dati veri e custodire le tue credenziali. Puoi chiudere l’account quando vuoi scrivendo a :email.',
                ],
            ],
            [
                'heading' => 'Abbonamento',
                'blocks' => [
                    'Salvare e riaprire le playlist richiede un abbonamento a :price, che si rinnova automaticamente ogni mese finché non lo disdici. Puoi disdirlo in qualsiasi momento dalla pagina Account: resta attivo fino alla fine del periodo già pagato. Se cambiamo il prezzo ti avvisiamo prima del rinnovo.',
                    'Gli ordini sono gestiti da Paddle.com, il nostro rivenditore (merchant of record), che si occupa di pagamenti, fatture e assistenza sugli ordini. All’acquisto si applicano anche i [termini per gli acquirenti di Paddle](https://www.paddle.com/legal/checkout-buyer-terms).',
                ],
            ],
            [
                'heading' => 'Rimborsi',
                'blocks' => [
                    'Puoi chiedere il rimborso di un pagamento entro 14 giorni, senza dover dare spiegazioni, scrivendo a :email o tramite [paddle.net](https://paddle.net). Il rimborso arriva sullo stesso metodo di pagamento.',
                ],
            ],
            [
                'heading' => 'Uso corretto',
                'blocks' => [
                    'Non usare Moodler per scopi illegali, non cercare di aggirarne i limiti, non accedervi in modo automatizzato (bot, scraping) e non sovraccaricarlo. Possiamo sospendere l’accesso a chi lo fa.',
                ],
            ],
            [
                'heading' => 'Contenuti di terzi',
                'blocks' => [
                    'Brani, copertine e video appartengono ai rispettivi titolari e sono mostrati tramite Spotify e YouTube. Usando il lettore accetti i [Termini di servizio di YouTube](https://www.youtube.com/t/terms).',
                ],
            ],
            [
                'heading' => 'Responsabilità',
                'blocks' => [
                    'Facciamo il possibile perché Moodler funzioni bene, ma il servizio è offerto così com’è e può avere interruzioni, anche per problemi dei servizi esterni che usa. Nei limiti consentiti dalla legge non rispondiamo dei danni indiretti. Restano salvi i diritti che la legge riconosce ai consumatori.',
                ],
            ],
            [
                'heading' => 'Modifiche e legge applicabile',
                'blocks' => [
                    'Possiamo aggiornare questi termini: per le modifiche importanti avvisiamo via email chi ha un account. Si applica la legge italiana, fatti salvi i diritti inderogabili che il paese in cui vivi riconosce ai consumatori.',
                ],
            ],
        ],
    ],
];
