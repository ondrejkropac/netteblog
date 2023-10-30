<?php

/*
 * Licence by měla být Premium commercial. Více informací na
 * http://www.itnetwork.cz/licence
 */

declare(strict_types=1);

namespace App\CoreModule\Model;

use App\Model\DatabaseManager;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\IRow;
use Nette\Database\Table\Selection;
use Nette\Utils\ArrayHash;

/**
 * Model pro správu článků v redakčním systému.
 * @package App\CoreModule\Model
 */
class ZapisnikManager extends DatabaseManager
{
    /** Konstanty pro práci s databází. */
    const
        TABLE_NAME = 'kroky', //zapisky
        COLUMN_ID = 'kroky_id',
        COLUMN_URL = 'url';

    /**
     * Vrátí seznam všech článků v databázi seřazený sestupně od naposledy přidaného.
     * @return Selection seznam všech článků
     */

    public function getZapisky(array $parameters): Selection {
        $zapisky = $this->database->table(self::TABLE_NAME);
        return $zapisky;
    }

    public function getAllZapisky(): Selection {
        return $this->database->table(self::TABLE_NAME)->order(self::COLUMN_ID . ' DESC');
    }

	public function getZapisekFromId(int $id, string $columns = NULL): IRow {
		return $this->database->table(self::TABLE_NAME)
			->select($columns ? $columns : '*')
			->where(self::COLUMN_ID, $id)
			->fetch();
	}

    /**
     * Vrátí článek z databáze podle jeho URL.
     * @param string $url URl článku
     * @return false|ActiveRow první článek, který odpovídá URL nebo false pokud článek s danou URL neexistuje
     */
    public function getZapisek($url)
    {
        return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_URL, $url)->fetch();
    }

    /**
     * Uloží článek do systému.
     * Pokud není nastaveno ID vloží nový článek, jinak provede editaci článku s daným ID.
     * @param array|ArrayHash $zapisek článek
     */
    public function saveZapisek(ArrayHash $zapisek)
    {
        if (empty($zapisek[self::COLUMN_ID])) {
            unset($zapisek[self::COLUMN_ID]);
            $this->database->table(self::TABLE_NAME)->insert($zapisek);
        } else
            $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $zapisek[self::COLUMN_ID])->update($zapisek);
    }

    /**
     * Odstraní článek s danou URL.
     * @param string $url URL článku
     */
    public function removeZapisek(string $url)
    {
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_URL, $url)->delete();
    }
}