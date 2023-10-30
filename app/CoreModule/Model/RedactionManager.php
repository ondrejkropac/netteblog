<?php

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
class RedactionManager extends DatabaseManager
{
    /** Konstanty pro práci s databází. */
    const
        TABLE_NAME = 'redakce',
        COLUMN_ID = 'redakce_id',
        COLUMN_STAV = 'stav';

    public function getAllRedactiontasks(): Selection {
        return $this->database->table(self::TABLE_NAME)->order(self::COLUMN_ID . ' DESC');
    }

    // stejně vše spojené s redakcí patří do presenteru redakce/administrativa rozhodni později!

    public function getRedactionFromId(int $id, string $columns = NULL): IRow {
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
    public function getRedaction($id)
    {
        return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $id)->fetch();
    }

    /**
     * Uloží úkol do systému.
     * Pokud není nastaveno ID vloží nový úkol, jinak provede editaci úkolu s daným ID.
     * @param array|ArrayHash $redaction úkol
     */
    public function saveRedaction(ArrayHash $redaction)
    {
        if (empty($redaction[self::COLUMN_ID])) {
            unset($redaction[self::COLUMN_ID]);
            $this->database->table(self::TABLE_NAME)->insert($redaction);
        } else
            $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $redaction[self::COLUMN_ID])->update($redaction);
    }
    
    /**
     * Změní toto s daným ID hodnotu stav.
     * @param int $id ID galerie
     */
    public function inworkRedaction(int $id, $stav = null)
    {
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, array($id))->update(array(self::COLUMN_STAV => $stav));
    }

    public function getStav($Id)
	{
		return $this->database->table(self::TABLE_NAME)->select(self::COLUMN_STAV)->where(self::COLUMN_ID, $Id)->fetchField(self::COLUMN_STAV);
	}

    /**
     * Odstraní úkol s danou URL.
     * @param string $url URL úkolu
     */
    public function removeRedaction(int $id)
    {
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $id)->delete();
    }
}