<?php

/*
 * Licence by měla být Premium commercial. Více informací na
 * http://www.itnetwork.cz/licence
 */

declare(strict_types=1);

namespace App\CoreModule\Model;

use App\Model\DatabaseManager;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Utils\ArrayHash;

/**
 * Model pro správu článků v redakčním systému.
 * @package App\CoreModule\Model
 */
class CategoryManager extends DatabaseManager
{
    /** Konstanty pro práci s databází. */
    const
        TABLE_NAME = 'category',
        COLUMN_ID = 'id',
        COLUMN_TITLE = 'name',
        COLUMN_URL = 'url';

    /**
     * Vrátí seznam všech kategorií v databázi seřazený sestupně od naposledy přidané.
     * @return Selection seznam všech kategorií
     */
    public function getCategories()
    {
        return $this->database->table(self::TABLE_NAME)->order(self::COLUMN_ID . ' DESC');
    }

    /**
     * Vrátí seznam všech kategorií v databázi  v asociativním poli.
     * @return Selection seznam všech kategorií
     */
    public function getAllCategory() {
        return $this->database->table(self::TABLE_NAME)->fetchPairs(self::COLUMN_ID, self::COLUMN_TITLE);
    }

        /**
     * Vrátí seznam všech kategorií v databázi seřazený sestupně od naposledy přidané.
     * @return Selection seznam všech kategorií
     */
    public function getAllCategoryName()
    {
        return $this->database->table(self::TABLE_NAME)->fetchPairs(self::COLUMN_URL, self::COLUMN_TITLE);
    }

    /**
     * Vrátí kategorii v databázi podle url... seřazený sestupně od naposledy přidané.
     * @return Selection seznam všech kategorií
     */
    public function getCategory(string $url): ActiveRow {
        return $this->database->table(self::TABLE_NAME)
                        ->where(self::COLUMN_URL, $url)->fetch();
    }
}