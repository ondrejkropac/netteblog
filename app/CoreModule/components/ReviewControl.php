<?php

/*  _____ _______         _                      _
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
 */

namespace App\CoreModule\components;

use App\Model\UserManager;
use DateTime;
use Nette\Application\UI\Control;
use Nette\Database\Table\IRow;

/**
 * Komponenta pro vykreslení recenze produktu.
 * @package App\EshopModule\Components
 */
class ReviewControl extends Control
{
    /** Cesta k souboru šablony pro tuto komponentu. */
    const TEMPLATE = __DIR__ . '/../templates/components/review.latte';

    /** var array České názvy měsíců */
    private static $months = array('ledna', 'února', 'března', 'dubna', 'května', 'června', 'července', 'srpna', 'září', 'října', 'listopadu', 'prosince');

    /** @var UserManager Instance třídy modelu pro práci s recenzemi. */
    private UserManager $userManager;

    /**
     * Konstruktor s nastavením URL výchozího článku a injektovaným modelem pro správu článků.
     * @param UserManager $galleryManager    automaticky injektovaný model pro správu článků
     */
    public function __construct(UserManager $userManager)
    {
        $this->userManager = $userManager;
    }

    /**
     * Vykreslí recenzi produktu.
     * @param IRow $review recenze produktu
     */
    public function render(IRow $review)
    {
        $this->template->setFile(self::TEMPLATE); // Nastaví šablonu komponenty.
        // Předává parametry do šablony.
        $this->template->review = $review;
        /** @var ActiveRow $user */
        $this->template->prettyDateTime = $this->prettyDateTime($review['sent']);
        $personName = $this->userManager->userName($review->user_id);
        $this->template->personName = $personName->username;
        $this->template->render(); // Vykreslí komponentu.
    }

    /**
     * Zformátuje datum a čas z libovolné stringové podoby na tvar např. "Dnes 15:21".
     * @param string $date datum ke zformátování
     * @return string zformátované datum
     */
    private function prettyDateTime($date)
    {
        if (ctype_digit($date)) $date = '@' . $date;
        $dateTime = new DateTime($date);
        return $this->getPrettyDate($dateTime) . $dateTime->format(' H:i:s');
    }

    /**
     * Zformátuje instanci DateTime na formát např. "Dnes".
     * @param DateTime $dateTime instance DateTime
     * @return string zformátovaná hodnota
     */
    private function getPrettyDate($dateTime)
    {
        $now = new DateTime();
        if ($dateTime->format('Y') != $now->format('Y'))
            return $dateTime->format('j.n.Y');
        $dayMonth = $dateTime->format('d-m');
        if ($dayMonth == $now->format('d-m'))
            return "Dnes";
        $now->modify('-1 DAY');
        if ($dayMonth == $now->format('d-m'))
            return "Včera";
        $now->modify('+2 DAYS');
        if ($dayMonth == $now->format('d-m'))
            return "Zítra";
        return $dateTime->format('j. ') . self::$months[$dateTime->format('n') - 1];
    }
}
