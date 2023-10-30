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
class TaskerManager extends DatabaseManager
{
    /** Konstanty pro práci s databází. */
    const
        TABLE_NAME = 'tasker',
        COLUMN_ID = 'tasker_id';

    /**
     * Vrátí seznam všech článků v databázi seřazený sestupně od naposledy přidaného.
     * @return Selection seznam všech článků
     */

    public function getTasks(array $parameters): Selection {
        $tasks = $this->database->table(self::TABLE_NAME);
        return $tasks;
    }

    public function getAllTasks(): Selection {
        return $this->database->table(self::TABLE_NAME)->order(self::COLUMN_ID . ' DESC');
    }

	public function getTaskFromId(int $id, string $columns = NULL): IRow {
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
    public function getTask($id)
    {
        return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $id)->fetch();
    }

    /**
     * Uloží článek do systému.
     * Pokud není nastaveno ID vloží nový článek, jinak provede editaci článku s daným ID.
     * @param array|ArrayHash $task článek
     */
    public function saveTask(ArrayHash $task)
    {
        unset($task['content']); // hodnota pro vykreslení formu / není v DB
        // nebudu mazat jedno po druhým z rozšířeného formu takže
        $taskerData = [
            'titulek' => $task['titulek'],
            'tasker_id' => $task['tasker_id'],
            'popisek' => $task['popisek'],
            'obsah' => $task['obsah'],
            'autor' => $task['autor'],
            'category' => $task['category'],
            //'notes' => $task['notes'], - unového hází chybu
        ];
        if (empty($task[self::COLUMN_ID])) {
            unset($task[self::COLUMN_ID]);
            $this->database->table(self::TABLE_NAME)->insert($taskerData);
        } else
            $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $task[self::COLUMN_ID])->update($taskerData);
    }

    /**
     * Odstraní článek s danou ID.
     * @param string $id ID článku
     */
    public function removeTask(string $id)
    {
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $id)->delete();
    }
}