/**  
    *  _____ _______         _                      _
    * |_   _|__   __|       | |                    | |
    *   | |    | |_ __   ___| |___      _____  _ __| | __  ___ ____
    *   | |    | | '_ \ / _ \ __\ \ /\ / / _ \| '__| |/ / / __|_  /
    *  _| |_   | | | | |  __/ |_ \ V  V / (_) | |  |   < | (__ / /
    * |_____|  |_|_| |_|\___|\__| \_/\_/ \___/|_|  |_|\_(_)___/___|
    *                                _
    *              ___ ___ ___ _____|_|_ _ _____
    *             | . |  _| -_|     | | | |     |
    *             |  _|_| |___|_|_|_|_|___|_|_|_|
    *             |_|                          _ _ _        LICENCE
    *        ___ ___    ___    ___ ___ ___ ___| | |_|___ ___
    *       |   | . |  |___|  |  _| -_|_ -| -_| | | |   | . |
    *       |_|_|___|         |_| |___|___|___|_|_|_|_|_|_  |
    *                                                   |___|
    *
    * IT ZPRAVODAJSTVÍ  <>  PROGRAMOVÁNÍ  <>  HW A SW  <>  KOMUNITA
    *
    * Tento zdrojový kód je součástí výukových seriálů na
    * IT sociální síti WWW.ITNETWORK.CZ
    *
    * Kód spadá pod licenci prémiového obsahu s omezeným
    * přeprodáváním a vznikl díky podpoře našich členů. Je určen
    * pouze pro osobní užití a nesmí být šířen. Může být použit
    * v jednom uzavřeném komerčním projektu, pro širší využití je
    * dostupná licence Premium commercial. Více informací na
    * http://www.itnetwork.cz/licence
    **/



/**
 * Hodnocení produktu pomocí hvězdiček
 */

 HTMLCollection.prototype.indexOf = [].indexOf

 $(document).ready(function() {
     $("#write-jreview").click(showReviewForm);
 });

 /**
  * Zobrazí/skryje formulář pro recenze
  */
 function showReviewForm() {
     $("#frm-reviewForm").slideToggle(500);
 }