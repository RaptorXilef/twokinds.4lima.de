/**
 * Kümmert sich um die clientseitige Logik für die Session-Timeout-Warnung und den sichtbaren Countdown.
 * Sendet Keep-Alive-Signale an den Server und verarbeitet Logout-Antworten (401).
 *
 * @file      ROOT/public/assets/js/session_timeout.min.js
 * @package   twokinds.4lima.de
 * @author    Felix M. (@RaptorXilef)
 * @copyright 2025 Felix M.
 * @license   Creative Commons Attribution-NonCommercial-Share-Alike 4.0 International <https://github.com/RaptorXilef/twokinds.4lima.de/blob/main/LICENSE>
 * @link      https://github.com/RaptorXilef/twokinds.4lima.de
 *
 * @since     3.0.0 - 4.0.0
 * - Pfadanpassung der keep_alive.php
 * @since 5.0.0
 * - Integration der 401-Redirect-Logik für abgelaufene Sessions.
 * - Wiederherstellung des visuellen Countdowns und der Activity-Handler.
 * - Konfiguration auf "Zeit vor Ablauf" umgestellt (statt "Zeit nach Start").
 * - Übergabe des Timeout-Grundes an den Logout-Prozess (forceLogout Parameter).
 * - Dynamische Werte aus window.sessionConfig (PHP) nutzen.
 */
document.addEventListener("DOMContentLoaded",()=>{const e=window.sessionConfig||{},t=e.timeoutSeconds||600,n=e.warningSeconds||60;let o,i,r,s,d=!1;const c=document.getElementById("sessionTimeoutModal"),l=document.getElementById("sessionTimeoutCountdown"),a=document.getElementById("stayLoggedInBtn"),u=document.getElementById("logoutBtn"),w=document.getElementById("session-timer-countdown"),m=(()=>{if(void 0!==window.csrfToken&&""!==window.csrfToken)return window.csrfToken;const e=document.querySelector('meta[name="csrf-token"]');return e?e.getAttribute("content"):""})();function v(){clearTimeout(o),clearTimeout(i),clearInterval(r),clearInterval(s);o=setTimeout(p,1e3*(t-n)),i=setTimeout(()=>h(!0),1e3*t),w&&function(){let e=t;const n=()=>{if(e<0)return void clearInterval(s);const t=Math.floor(e/60).toString().padStart(2,"0"),n=(e%60).toString().padStart(2,"0");w&&(w.textContent=`${t}:${n}`),e--};n(),s=setInterval(n,1e3)}()}function p(){c&&(c.style.display="flex");let e=n;l&&(l.textContent=e),r=setInterval(()=>{e--,l&&(l.textContent=e),e<=0&&(clearInterval(r),h(!0))},1e3)}function f(){if(d)return;d=!0;const e=new FormData;m&&e.append("csrf_token",m);const t=void 0!==window.keepAliveUrl&&window.keepAliveUrl?window.keepAliveUrl:"keep_alive.php";fetch(t,{method:"POST",body:e}).then(e=>{if(401===e.status)return e.json().then(e=>{e.redirect?window.location.href=e.redirect:window.location.href="index.php?reason=session_expired"}).catch(()=>{window.location.href="index.php?reason=session_expired"});e.ok?v():console.warn("Keep-Alive Ping nicht OK:",e.status)}).catch(e=>{console.error("Keep-Alive Fehler:",e)}).finally(()=>{setTimeout(()=>{d=!1},5e3)})}function h(e=!1){let t="?action=logout";m&&(t+=`&token=${m}`),e&&(t+="&timeout=true"),window.location.href=t}let g;a&&a.addEventListener("click",()=>{c&&(c.style.display="none"),clearInterval(r),f()}),u&&u.addEventListener("click",()=>h(!1));const k=()=>{clearTimeout(g),g=setTimeout(()=>{f()},500)};window.addEventListener("mousemove",k,{passive:!0}),window.addEventListener("keydown",k,{passive:!0}),window.addEventListener("click",k,{passive:!0}),window.addEventListener("scroll",k,{passive:!0}),v()});