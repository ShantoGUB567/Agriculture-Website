<?php
/**
 * Google Translate Integration for FarmKnowledge Website
 * This file provides translation functionality across all pages
 */

// HTML for the language selection dropdown
function get_language_selector() {
    return '
    <div class="translate-container" style="position: fixed; top: 20px; right: 20px; z-index: 1000;">
        <select id="google-translate-select" class="language-selector-dropdown">
            <option value="" selected>🌐 Language</option>
            <option value="en">English</option>
            <option value="bn">বাংলা (Bengali)</option>
            <option value="es">Español</option>
            <option value="fr">Français</option>
            <option value="hi">हिन्दी</option>
            <option value="de">Deutsch</option>
            <option value="zh-CN">中文 (简体)</option>
            <option value="ar">العربية</option>
            <option value="pt">Português</option>
            <option value="ru">Русский</option>
            <option value="ja">日本語</option>
        </select>
    </div>';
}

// JavaScript for Google Translate functionality
function get_translate_javascript() {
    return '
    <!-- Google Translate API Script -->
    <div id="google_translate_element" style="display:none;"></div>
    <script type="text/javascript">
    function googleTranslateElementInit() {
        new google.translate.TranslateElement({
            pageLanguage: "en",
            includedLanguages: "en,bn,es,fr,hi,de,zh-CN,ar,pt,ru,ja", 
            layout: google.translate.TranslateElement.InlineLayout.SIMPLE,
            autoDisplay: false
        }, "google_translate_element");
    }
    
    // Handle language selection change with cookie method
    document.addEventListener("DOMContentLoaded", function() {
        const translateSelect = document.getElementById("google-translate-select");
        
        // Wait for Google Translate to initialize
        setTimeout(function() {
            translateSelect.addEventListener("change", function() {
                const langCode = this.value;
                if (langCode) {
                    // Use cookie approach to change language
                    doGTranslate("en|" + langCode);
                }
            });
        }, 1000);
        
        // Function to set Google Translate cookies and trigger translation
        function doGTranslate(lang_pair) {
            if (lang_pair.value) lang_pair = lang_pair.value;
            if (lang_pair == "") return;
            var lang = lang_pair.split("|")[1];
            // if no target language is specified use the browser preferred language
            if (lang == undefined) lang = getBrowserLanguage();
            
            // Create a cookie for Google Translate
            createCookie("googtrans", "/en/" + lang, 1);
            
            // Reload the page to apply the translation
            location.reload();
        }
        
        function createCookie(name, value, days) {
            var expires = "";
            if (days) {
                var date = new Date();
                date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
                expires = "; expires=" + date.toUTCString();
            }
            document.cookie = name + "=" + value + expires + "; path=/; domain=" + document.domain;
            document.cookie = name + "=" + value + expires + "; path=/; domain=." + document.domain;
        }
        
        function getBrowserLanguage() {
            var browserLang = navigator.language || navigator.userLanguage;
            browserLang = browserLang.split("-")[0];
            return browserLang;
        }
    });
    </script>
    <script type="text/javascript" src="//translate.google.com/translate_a/element.js?cb=googleTranslateElementInit"></script>
    
    <!-- Add custom styles to improve Google Translate widget appearance -->
    <style>
        /* Hide Google\'s default language bar */
        .goog-te-banner-frame {
            display: none !important;
        }
        body {
            top: 0 !important;
        }
        /* Adjust any elements affected by Google Translate */
        .skiptranslate {
            display: none !important;
        }
        
        /* Enhanced styling for language selector dropdown */
        .language-selector-dropdown {
            background: #2e7d32 !important;
            color: white !important;
            border: 2px solid #fff !important;
            padding: 6px 12px !important;
            border-radius: 4px !important;
            font-weight: 500 !important;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2) !important;
            cursor: pointer !important;
            transition: all 0.3s ease !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
            background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'12\' height=\'12\' viewBox=\'0 0 12 12\'%3E%3Cpath fill=\'white\' d=\'M6 9.5l-4-4h8z\'/%3E%3C/svg%3E") !important;
            background-repeat: no-repeat !important;
            background-position: right 10px center !important;
            background-size: 10px !important;
            padding-right: 30px !important;
        }
        
        .language-selector-dropdown:hover, .language-selector-dropdown:focus {
            background: #1b5e20 !important;
            border-color: #e8f5e9 !important;
            box-shadow: 0 4px 8px rgba(0,0,0,0.3) !important;
        }
        
        /* Mobile responsive styles */
        @media (max-width: 768px) {
            .translate-container {
                margin-bottom: 10px;
            }
            .language-selector-dropdown {
                padding: 5px 10px !important;
            }
        }
    </style>';
}
?>