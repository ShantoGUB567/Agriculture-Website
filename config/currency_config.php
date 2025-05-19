<?php
// Define exchange rates relative to USD
$exchange_rates = [
    'USD' => 1.00,    // Base currency
    'EUR' => 0.85,    // Euro
    'GBP' => 0.74,    // British Pound
    'JPY' => 110.25,  // Japanese Yen
    'CAD' => 1.23,    // Canadian Dollar
    'AUD' => 1.34,    // Australian Dollar
    'INR' => 74.50,   // Indian Rupee
    'CNY' => 6.45,    // Chinese Yuan
    'BDT' => 109.85   // Bangladeshi Taka
];

// Currency symbols
$currency_symbols = [
    'USD' => '$',
    'EUR' => '€',
    'GBP' => '£',
    'JPY' => '¥',
    'CAD' => 'C$',
    'AUD' => 'A$',
    'INR' => '₹',
    'CNY' => '¥',
    'BDT' => '৳'     // Bangladeshi Taka symbol (Taka)
];

// Function to convert price from USD to target currency
function convertCurrency($price, $target_currency, $exchange_rates) {
    if (!array_key_exists($target_currency, $exchange_rates)) {
        return $price; // Return original price if currency not found
    }
    return $price * $exchange_rates[$target_currency];
}

// Function to format price with correct currency symbol
function formatPrice($price, $currency, $currency_symbols) {
    if (!array_key_exists($currency, $currency_symbols)) {
        return '$' . number_format($price, 2); // Default to USD format if symbol not found
    }
    
    // Special formatting for certain currencies
    if ($currency === 'JPY') {
        return $currency_symbols[$currency] . number_format($price, 0); // No decimal places for JPY
    }
    
    // Special formatting for BDT (Taka symbol typically appears after the amount)
    if ($currency === 'BDT') {
        return number_format($price, 2) . ' ' . $currency_symbols[$currency]; // Place symbol after amount
    }
    
    return $currency_symbols[$currency] . number_format($price, 2);
}
