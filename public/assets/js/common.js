/**
 * Dies is der JS Code zum umschalten zwischen den Themen Dark/Normal
 *
 * @file      ROOT/ressources/js/common.js / Minificed: ROOT/public/assets/js/common.min.js
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-ShareAlike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 * @version   1.1.0
 * @since     1.1.0 Der Button zum Umschalten des Webseiten-Themas (Light- / Dark-Mode) in der Seitenleiste wurde deaktiviert. Damit soll die Benutzererfahrung beim Umgang mit dem Fehler-Modal verbessert und ein ungewolltes umschalten verhindert werden.
 */
(()=>{var e=[{id:0,name:"Default",class:null},{id:1,name:"Lights On",class:null},{id:2,name:"Lights Off",class:"theme-night"}],t=0;function a(a){void 0!==a&&a.preventDefault(),o((t+1)%e.length,!0,!0)}function o(a,n,s){var i=document.getElementsByTagName("body")[0],l=e[a],d=0==a;i.classList.forEach(e=>{e.startsWith("theme-")&&i.classList.remove(e)}),s&&i.classList.add("transitioning"),d&&function(e){window.matchMedia&&window.matchMedia("(prefers-color-scheme: dark)").matches?o(2,!1,e):o(1,!1,e)}(s),null!=l.class&&i.classList.add(l.class),document.querySelector("#toggle_lights .themename").innerHTML=l.name,n&&void 0!==window.localStorage&&(d?window.localStorage.removeItem("themePref"):window.localStorage.setItem("themePref",a)),t=a,function(){var e=document.getElementsByTagName("body")[0];window.setTimeout(()=>{e.classList.remove("transitioning"),e.classList.remove("preload")},300)}()}document.addEventListener("DOMContentLoaded",()=>{var e=document.getElementsByTagName("body")[0];document.querySelectorAll(".jsdep").forEach(e=>e.classList.remove("jsdep")),document.querySelector("#toggle_lights").addEventListener("click",a),void 0!==window.localStorage?void 0===window.localStorage.themePref?o(0,!1,!1):o(window.localStorage.themePref,!1,!1):e.classList.remove("preload"),window.matchMedia&&window.matchMedia("(prefers-color-scheme: dark)").addEventListener("change",()=>{0==t&&o(0,!1,!0)})})})();