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
    'contacts' => [
        'nom' => 'DynamoMenu Restaurant',
        'adresse' => 'Kinshasa, République Démocratique du Congo',
        'telephone' => '+243 900 000 000',
        'email' => 'contact@dynamomenu.fr',
        'whatsapp' => '+243 900 000 001',
        'horaires' => 'Lun–Dim : 11h00 – 23h00',
    ],
];
