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
class EventManager extends DatabaseManager
{
    /** Konstanty pro práci s databází. */
    const
        TABLE_NAME = 'event', //events
        COLUMN_ID = 'event_id';

    /**
     * Vrátí seznam všech článků v databázi seřazený sestupně od naposledy přidaného.
     * @return Selection seznam všech events
     */

    public function getAllEvents(): Selection {
        return $this->database->table(self::TABLE_NAME)->order(self::COLUMN_ID . ' DESC');
    }

	public function getEventFromId(int $id, string $columns = NULL): IRow {
		return $this->database->table(self::TABLE_NAME)
			->select($columns ? $columns : '*')
			->where(self::COLUMN_ID, $id)
			->fetch();
	}

    /**
     * Vrátí článek z databáze podle jeho ID.
     * @param string $id Id článku
     * @return false|ActiveRow první článek, který odpovídá ID nebo false pokud článek s danou ID neexistuje
     */
    public function getEvent($id)
    {
        return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $id)->fetch();
    }

    /**
     * Uloží článek do systému.
     * Pokud není nastaveno ID vloží nový článek, jinak provede editaci článku s daným ID.
     * @param array|ArrayHash $event článek
     */
    public function saveEvent(ArrayHash $event)
    {
        if (empty($event[self::COLUMN_ID])) {
            unset($event[self::COLUMN_ID]);
            $this->database->table(self::TABLE_NAME)->insert($event);
        } else
            $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $event[self::COLUMN_ID])->update($event);
    }

    /**
     * Odstraní článek s danou ID.
     * @param string $id ID článku
     */
    public function removeEvent(int $id)
    {
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $id)->delete();
    }
}