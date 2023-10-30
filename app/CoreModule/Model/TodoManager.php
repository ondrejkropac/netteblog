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
 * Model pro správu úkolů v redakčním systému.
 * @package App\CoreModule\Model
 */
class TodoManager extends DatabaseManager
{
    /** Konstanty pro práci s databází. */
    const
        TABLE_NAME = 'todo', //todos
        COLUMN_STAV = 'stav',
        COLUMN_DEV = 'dev',
        COLUMN_ID = 'todo_id';

    /**
     * Vrátí seznam všech úkolů v databázi seřazený sestupně od naposledy přidaného.
     * @return Selection seznam všech úkolů
     */

    /*public function getTodos(array $parameters): Selection {
        $todos = $this->database->table(self::TABLE_NAME);
        return $todos;
    }*/

    public function getAllTodos(): Selection {
        return $this->database->table(self::TABLE_NAME)->order(self::COLUMN_ID . ' DESC');
    }

	public function getTodoFromId(int $id, string $columns = NULL): IRow {
		return $this->database->table(self::TABLE_NAME)
			->select($columns ? $columns : '*')
			->where(self::COLUMN_ID, $id)
			->fetch();
	}

    /**
     * Vrátí úkol z databáze podle jeho URL.
     * @param string $url URl úkolu
     * @return false|ActiveRow první úkol, který odpovídá URL nebo false pokud úkol s danou URL neexistuje
     */
    public function getTodo($id)
    {
        return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $id)->fetch();
    }

    /**
     * Uloží úkol do systému.
     * Pokud není nastaveno ID vloží nový úkol, jinak provede editaci úkolu s daným ID.
     * @param array|ArrayHash $todo úkol
     */
    public function saveTodo(ArrayHash $todo)
    {
        if (empty($todo[self::COLUMN_ID])) {
            unset($todo[self::COLUMN_ID]);
            $this->database->table(self::TABLE_NAME)->insert($todo);
        } else
            $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $todo[self::COLUMN_ID])->update($todo);
    }
    
    /**
     * Změní toto s daným ID hodnotu stav.
     * @param int $id ID galerie
     */
    public function inworkTodo(int $id, $stav = null)
    {
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, array($id))->update(array(self::COLUMN_STAV => $stav));
    }

    public function devTodo(int $id, $dev = null)
    {
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, array($id))->update(array(self::COLUMN_DEV => $dev));
    }

    public function getStav($Id)
	{
		return $this->database->table(self::TABLE_NAME)->select(self::COLUMN_STAV)->where(self::COLUMN_ID, $Id)->fetchField(self::COLUMN_STAV);
	}

    /**
     * Odstraní úkol s danou URL.
     * @param string $url URL úkolu
     */
    public function removeTodo(int $id)
    {
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $id)->delete();
    }
}