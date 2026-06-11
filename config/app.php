<?php

/**
 * URL publique du projet (sans slash final).
 * Adaptez si votre virtual host diffère (ex. http://localhost/DynamoMenu).
 */
return [
    'base_url' => 'http://localhost/DynamoMenu',
    'currency_code' => 'CDF',
    'currency_symbol' => 'FC',
    /** Taux : 1 unité menu (ancien tarif euro) × ce multiplicateur = franc congolais */
    'eur_to_cdf' => 2800,
    'currency_decimals' => 0,
    /** Clé secrète pour tokens commande — changez en production */
    'session_secret' => 'change-me-in-production',
    /** Durée session staff (secondes) — défaut 8 h */
    'staff_session_lifetime' => 28800,
    /** Durée session client (secondes) — défaut 4 h */
    'client_session_lifetime' => 14400,
    /** Autoriser init_db.php / run_update.php via navigateur (admin requis) */
    'allow_web_setup' => true,
    'contacts' => [
        'nom' => 'DynamoMenu Restaurant',
        'infos' => 'Restaurant avec service sur place. Commandez depuis votre table via le menu digital.',
        'adresse' => 'Kinshasa, République Démocratique du Congo',
        'telephone' => '+243 900 000 000',
        'email' => 'contact@dynamomenu.fr',
        'whatsapp' => '+243 900 000 001',
        'horaires' => 'Lun–Dim : 11h00 – 23h00',
    ],
];
