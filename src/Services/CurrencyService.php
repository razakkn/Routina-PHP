<?php

namespace Routina\Services;

class CurrencyService {
    /**
     * ISO 4217 currency codes -> display names.
     * Names are plain-English labels for usability.
     */
    public static function all(): array {
        // NOTE: Keep sorted by code for fast scanning.
        return [
            'AED' => 'United Arab Emirates Dirham',
            'AFN' => 'Afghan Afghani',
            'ALL' => 'Albanian Lek',
            'AMD' => 'Armenian Dram',
            'ANG' => 'Netherlands Antillean Guilder',
            'AOA' => 'Angolan Kwanza',
            'ARS' => 'Argentine Peso',
            'AUD' => 'Australian Dollar',
            'AWG' => 'Aruban Florin',
            'AZN' => 'Azerbaijani Manat',
            'BAM' => 'Bosnia-Herzegovina Convertible Mark',
            'BBD' => 'Barbadian Dollar',
            'BDT' => 'Bangladeshi Taka',
            'BGN' => 'Bulgarian Lev',
            'BHD' => 'Bahraini Dinar',
            'BIF' => 'Burundian Franc',
            'BMD' => 'Bermudian Dollar',
            'BND' => 'Brunei Dollar',
            'BOB' => 'Bolivian Boliviano',
            'BOV' => 'Bolivian Mvdol (funds code)',
            'BRL' => 'Brazilian Real',
            'BSD' => 'Bahamian Dollar',
            'BTN' => 'Bhutanese Ngultrum',
            'BWP' => 'Botswana Pula',
            'BYN' => 'Belarusian Ruble',
            'BZD' => 'Belize Dollar',
            'CAD' => 'Canadian Dollar',
            'CDF' => 'Congolese Franc',
            'CHE' => 'WIR Euro (complementary currency)',
            'CHF' => 'Swiss Franc',
            'CHW' => 'WIR Franc (complementary currency)',
            'CLP' => 'Chilean Peso',
            'CLF' => 'Chilean Unidad de Fomento (funds code)',
            'CNY' => 'Chinese Yuan',
            'COP' => 'Colombian Peso',
            'COU' => 'Colombian Unidad de Valor Real (funds code)',
            'CRC' => 'Costa Rican Colón',
            'CUP' => 'Cuban Peso',
            'CVE' => 'Cape Verdean Escudo',
            'CZK' => 'Czech Koruna',
            'DJF' => 'Djiboutian Franc',
            'DKK' => 'Danish Krone',
            'DOP' => 'Dominican Peso',
            'DZD' => 'Algerian Dinar',
            'EGP' => 'Egyptian Pound',
            'ERN' => 'Eritrean Nakfa',
            'ETB' => 'Ethiopian Birr',
            'EUR' => 'Euro',
            'FJD' => 'Fijian Dollar',
            'FKP' => 'Falkland Islands Pound',
            'GBP' => 'British Pound Sterling',
            'GEL' => 'Georgian Lari',
            'GHS' => 'Ghanaian Cedi',
            'GIP' => 'Gibraltar Pound',
            'GMD' => 'Gambian Dalasi',
            'GNF' => 'Guinean Franc',
            'GTQ' => 'Guatemalan Quetzal',
            'GYD' => 'Guyanese Dollar',
            'HKD' => 'Hong Kong Dollar',
            'HNL' => 'Honduran Lempira',
            'HTG' => 'Haitian Gourde',
            'HUF' => 'Hungarian Forint',
            'IDR' => 'Indonesian Rupiah',
            'ILS' => 'Israeli New Shekel',
            'INR' => 'Indian Rupee',
            'IQD' => 'Iraqi Dinar',
            'IRR' => 'Iranian Rial',
            'ISK' => 'Icelandic Króna',
            'JMD' => 'Jamaican Dollar',
            'JOD' => 'Jordanian Dinar',
            'JPY' => 'Japanese Yen',
            'KES' => 'Kenyan Shilling',
            'KGS' => 'Kyrgyzstani Som',
            'KHR' => 'Cambodian Riel',
            'KMF' => 'Comorian Franc',
            'KPW' => 'North Korean Won',
            'KRW' => 'South Korean Won',
            'KWD' => 'Kuwaiti Dinar',
            'KYD' => 'Cayman Islands Dollar',
            'KZT' => 'Kazakhstani Tenge',
            'LAK' => 'Lao Kip',
            'LBP' => 'Lebanese Pound',
            'LKR' => 'Sri Lankan Rupee',
            'LRD' => 'Liberian Dollar',
            'LSL' => 'Lesotho Loti',
            'LYD' => 'Libyan Dinar',
            'MAD' => 'Moroccan Dirham',
            'MDL' => 'Moldovan Leu',
            'MGA' => 'Malagasy Ariary',
            'MKD' => 'Macedonian Denar',
            'MMK' => 'Myanmar Kyat',
            'MNT' => 'Mongolian Tögrög',
            'MOP' => 'Macanese Pataca',
            'MRU' => 'Mauritanian Ouguiya',
            'MUR' => 'Mauritian Rupee',
            'MVR' => 'Maldivian Rufiyaa',
            'MWK' => 'Malawian Kwacha',
            'MXN' => 'Mexican Peso',
            'MXV' => 'Mexican Unidad de Inversion (funds code)',
            'MYR' => 'Malaysian Ringgit',
            'MZN' => 'Mozambican Metical',
            'NAD' => 'Namibian Dollar',
            'NGN' => 'Nigerian Naira',
            'NIO' => 'Nicaraguan Córdoba',
            'NOK' => 'Norwegian Krone',
            'NPR' => 'Nepalese Rupee',
            'NZD' => 'New Zealand Dollar',
            'OMR' => 'Omani Rial',
            'PAB' => 'Panamanian Balboa',
            'PEN' => 'Peruvian Sol',
            'PGK' => 'Papua New Guinean Kina',
            'PHP' => 'Philippine Peso',
            'PKR' => 'Pakistani Rupee',
            'PLN' => 'Polish Złoty',
            'PYG' => 'Paraguayan Guaraní',
            'QAR' => 'Qatari Riyal',
            'RON' => 'Romanian Leu',
            'RSD' => 'Serbian Dinar',
            'RUB' => 'Russian Ruble',
            'RWF' => 'Rwandan Franc',
            'SAR' => 'Saudi Riyal',
            'SBD' => 'Solomon Islands Dollar',
            'SCR' => 'Seychellois Rupee',
            'SDG' => 'Sudanese Pound',
            'SEK' => 'Swedish Krona',
            'SGD' => 'Singapore Dollar',
            'SHP' => 'Saint Helena Pound',
            'SLE' => 'Sierra Leonean Leone',
            'SLL' => 'Sierra Leonean Leone (old)',
            'SOS' => 'Somali Shilling',
            'SRD' => 'Surinamese Dollar',
            'SSP' => 'South Sudanese Pound',
            'STN' => 'São Tomé and Príncipe Dobra',
            'SYP' => 'Syrian Pound',
            'SZL' => 'Swazi Lilangeni',
            'THB' => 'Thai Baht',
            'TJS' => 'Tajikistani Somoni',
            'TMT' => 'Turkmenistan Manat',
            'TND' => 'Tunisian Dinar',
            'TOP' => 'Tongan Paʻanga',
            'TRY' => 'Turkish Lira',
            'TTD' => 'Trinidad and Tobago Dollar',
            'TWD' => 'New Taiwan Dollar',
            'TZS' => 'Tanzanian Shilling',
            'UAH' => 'Ukrainian Hryvnia',
            'UGX' => 'Ugandan Shilling',
            'USN' => 'United States Dollar (next day) (funds code)',
            'USD' => 'United States Dollar',
            'UYU' => 'Uruguayan Peso',
            'UYI' => 'Uruguay Peso en Unidades Indexadas (funds code)',
            'UZS' => 'Uzbekistani Soʻm',
            'VES' => 'Venezuelan Bolívar',
            'VND' => 'Vietnamese Đồng',
            'VUV' => 'Vanuatu Vatu',
            'WST' => 'Samoan Tālā',
            'XAF' => 'Central African CFA Franc',
            'XAG' => 'Silver (one troy ounce)',
            'XAU' => 'Gold (one troy ounce)',
            'XBA' => 'European Composite Unit (EURCO) (bond markets unit)',
            'XBB' => 'European Monetary Unit (EMU-6) (bond markets unit)',
            'XBC' => 'European Unit of Account 9 (EUA-9) (bond markets unit)',
            'XBD' => 'European Unit of Account 17 (EUA-17) (bond markets unit)',
            'XCD' => 'East Caribbean Dollar',
            'XDR' => 'Special Drawing Rights (IMF)',
            'XOF' => 'West African CFA Franc',
            'XPD' => 'Palladium (one troy ounce)',
            'XPF' => 'CFP Franc',
            'XPT' => 'Platinum (one troy ounce)',
            'XSU' => 'SUCRE (regional settlement currency)',
            'XTS' => 'Code reserved for testing',
            'XUA' => 'ADB Unit of Account',
            'XXX' => 'No Currency',
            'YER' => 'Yemeni Rial',
            'ZAR' => 'South African Rand',
            'ZMW' => 'Zambian Kwacha',
            'ZWL' => 'Zimbabwean Dollar',
        ];
    }

    public static function normalizeCode($code): string {
        $code = strtoupper(trim((string)$code));
        // Strip whitespace and common separators
        $code = preg_replace('/[^A-Z]/', '', $code);
        if (!is_string($code)) {
            return '';
        }
        return $code;
    }

    public static function isValidCode($code): bool {
        $code = self::normalizeCode($code);
        return (bool)preg_match('/^[A-Z]{3}$/', $code);
    }

    public static function labelFor($code): string {
        $code = self::normalizeCode($code);
        $all = self::all();
        if (isset($all[$code])) {
            return $all[$code];
        }
        return $code;
    }

    /**
     * Best-effort display symbol/prefix for a currency code.
     * For ambiguous "$" currencies, we prefer an explicit prefix (e.g. "A$", "C$").
     */
    public static function symbolFor($code): string {
        $code = self::normalizeCode($code);
        $map = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY' => '¥',
            'CNY' => '¥',
            'INR' => '₹',
            'KRW' => '₩',
            'RUB' => '₽',
            'TRY' => '₺',

            'AUD' => 'A$',
            'CAD' => 'C$',
            'NZD' => 'NZ$',
            'HKD' => 'HK$',
            'SGD' => 'S$',

            'ZAR' => 'R',
            'BRL' => 'R$',
            'MXN' => 'MX$',
            'ARS' => 'AR$',

            'AED' => 'AED',
            'SAR' => 'SAR',
            'QAR' => 'QAR',
            'KWD' => 'KWD',
            'BHD' => 'BHD',
            'OMR' => 'OMR',

            'CHF' => 'CHF',
            'SEK' => 'SEK',
            'NOK' => 'NOK',
            'DKK' => 'DKK',
        ];

        if ($code !== '' && isset($map[$code])) {
            return $map[$code];
        }

        // Fallback: show code as prefix.
        return ($code !== '') ? $code : 'USD';
    }

    public static function formatMoney($amount, $code, int $decimals = 2): string {
        $symbol = self::symbolFor($code);
        $formatted = number_format((float)$amount, $decimals);
        // If symbol is actually a code prefix, separate by space.
        if (preg_match('/^[A-Z]{3}$/', $symbol)) {
            return $symbol . ' ' . $formatted;
        }
        return $symbol . $formatted;
    }
}
