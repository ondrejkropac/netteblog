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
class ReviewManager extends DatabaseManager
{
    /** Konstanty pro manipulaci s modelem. */
    const
        TABLE_NAME = 'review',
        ITEM_ID = 'gallery_id',
        COLUMN_ID = 'review_id';

        /**
 * Vrátí recenze k danému produktu.
 * @param int $galleryId ID produktu
 * @return Selection recenze k danému produktu
 */
public function getReviews($galleryId)
{
    return $this->database->table(self::TABLE_NAME)
        ->where(self::ITEM_ID, $galleryId)
        ->order(self::COLUMN_ID . ' DESC');
}

/**
 * Přidá k produktu recenzi.
 * @param array|ArrayHash $review recenze
 * @throws UniqueConstraintViolationException Pokud se daný uživatel pokusí hodnotit stejný produkt vícekrát.
 */
public function addReview($review)
{
	$review['sent'] = Explorer::literal('NOW()'); // Přidá aktuální datum a čas.
	$this->database->table(self::TABLE_NAME)->insert($review);
}
    
}

