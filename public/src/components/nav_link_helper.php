<?php
/**
 * Hilfsfunktion zum Rendern eines Navigationsbuttons.
 * Erzeugt entweder einen <a>-Tag (wenn nicht deaktiviert) oder einen <span>-Tag (wenn deaktiviert)
 * mit den entsprechenden Klassen und Texten.
 * 
 * @file      ROOT/public/src/components/nav_link_helper.php
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   4.0.0
 * @since     4.0.0 Überprüfung und Bestätigung der Kompatibilität mit der Path-Klasse.
 *
 * @param string $href Der href-Wert des Links oder '#' wenn deaktiviert.
 * @param string $class Die CSS-Klasse(n) für den Button (z.B. 'navbegin', 'navprev').
 * @param string $text Der angezeigte Text des Buttons.
 * @param bool $isDisabled Ob der Button deaktiviert sein soll.
 * @return string Der generierte HTML-String für den Navigationsbutton.
 */

// === DEBUG-MODUS STEUERUNG ===
$debugMode = $debugMode ?? false;

if (!function_exists('renderNavLink')) {
    function renderNavLink(string $href, string $class, string $text, bool $isDisabled): string
    {
        $tag = $isDisabled ? 'span' : 'a'; // Tag ist <span> wenn deaktiviert, sonst <a>
        $linkAttr = $isDisabled ? '' : ' href="' . htmlspecialchars($href) . '"'; // href nur für <a>
        $disabledClass = $isDisabled ? ' disabled' : ''; // 'disabled' Klasse hinzufügen, wenn deaktiviert
        return '<' . $tag . $linkAttr . ' class="navarrow ' . $class . $disabledClass . '">' .
            '    <span class="nav-wrapper"><span class="nav-text">' . htmlspecialchars($text) . '</span></span>' .
            '</' . $tag . '>';
    }
}
?>