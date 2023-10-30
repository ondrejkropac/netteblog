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

namespace App\CoreModule\Model;

use App\Model\DatabaseManager;
use Nette\Database\Explorer;
use Nette\Database\Table\Selection;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\ArrayHash;

/**
 * Správce recenzí na produkty.
 * @package App\CoreModule\Model
 */
class ServisManager extends DatabaseManager
{
    /** Konstanty pro manipulaci s modelem. */
    const
        TABLE_NAME = 'servis',
        COLUMN_SETTING = 'setting',
        COLUMN_NAME = 'name';

    /**
     * Vrátí všechny nastavení.
     * @return Selection nastavení
     */
    public function getSettings()
    {
        return $this->database->table(self::TABLE_NAME)
            ->order(self::COLUMN_NAME . ' DESC');
    }
    /**
    * Vrátí nastavení k danému hodnotě.
    * @param string $name jméno nastavené hodnoty
    * @return Selection nastavení dané hodnoty
    */
    public function getSetting($name)
    {
    return $this->database->table(self::TABLE_NAME)
        ->where(self::COLUMN_NAME, $name)
        ->fetch();
    }

    /**
     * Přidá k produktu recenzi.
     * @param array|ArrayHash $review recenze
     * @throws UniqueConstraintViolationException Pokud se daný uživatel pokusí hodnotit stejný produkt vícekrát.
     */
    public function addSetting($setting)
    {
        $setting['sent'] = Explorer::literal('NOW()'); // Přidá aktuální datum a čas.
        $this->database->table(self::TABLE_NAME)->update($setting);
    }

    /**
     * Nastaví úvodní obrázek.
     * @return Selection nastavení
     */
    public function homepageSetting()
    {
        $homePagePicture = $this->getSetting('homepage'); //dump($homePagePicture->setting);
            
        /** Cesta k obrázkům produktů. */
        
        //private string $GALLERY_IMAGES_PATH = '%wwwDir%/images/gallery/';
        //print_r($GALLERY_IMAGES_PATH . $homePagePicture);
        copy('images/gallery/' . $homePagePicture->setting, 'images/homepage/homepagePicture.jpg');
    }
}

