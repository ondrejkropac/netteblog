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
use Nette\Database\Context;
use Nette\Database\Explorer;
use Nette\Utils\Image;

/**
 * Model pro správu článků v redakčním systému.
 * @package App\CoreModule\Model
 */
class OnlineManager extends DatabaseManager
{
    /** @var string */
    private $picturePath;

    /** Konstanty pro práci s databází. */
    const
        TABLE_NAME = 'online',
        COLUMN_ID = 'online_id';

    public function __construct(Context $database, string $picturePath) {
        parent::__construct($database);
        $this->picturePath = $picturePath;
    }

    /**
     * Vrátí seznam všech článků v databázi seřazený sestupně od naposledy přidaného.
     * @return Selection seznam všech článků
     */

    public function getOnlines(): Selection { //duplicita defalt/online  a default list
        $onlines = $this->database->table(self::TABLE_NAME)->order(self::COLUMN_ID . ' DESC');   
        return $onlines;
    }

    public function getAllOnlines(): Selection { // použito list obnline
        return $this->database->table(self::TABLE_NAME);
    }

	public function getOnlineFromId(int $id, string $columns = NULL): IRow {
		return $this->database->table(self::TABLE_NAME)
			->select($columns ? $columns : '*')
			->where(self::COLUMN_ID, $id)
			->fetch();
	}

    /**
     * Vrátí článek z databáze podle jeho TITLE.
     * @param string $titulek TIT článku
     * @return false|ActiveRow první článek, který odpovídá TITLE nebo false pokud článek s danou TITLE neexistuje
     */
    public function getOnline($id)
    {
        return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $id)->fetch();
    }

    public function getOnlinePosledni() // jen pro třídu list~! 
    {
        return $this->database->table(self::TABLE_NAME)->order(self::COLUMN_ID . ' DESC')->fetch();//poslední
    }

    /**
     * Uloží článek do systému.
     * Pokud není nastaveno ID vloží nový článek, jinak provede editaci článku s daným ID.
     * @param array|ArrayHash $online článek
     */
    public function saveOnline(ArrayHash $online)
    {
        $pic = $online['picture'];
        unset($online['picture']);
        
        if (empty($online[self::COLUMN_ID])) {
            unset($online[self::COLUMN_ID]);
            $online = $this->database->table(self::TABLE_NAME)->insert($online);
        } else
            $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $online[self::COLUMN_ID])->update($online);
        
        if (!empty($pic) && $pic->isOk()) {
            /** @var Image $im */
            $im = $pic->toImage();
            $im->resize(600, 400, Image::EXACT);
            $im->save('images/online/online_' . $online->online_id . '.jpg', 90, Image::JPEG); // cestu $this->picturePath dočasně nastavuju na images
            /*
            $im->resize(900, 400, Image::EXACT);
            $im->save(sprintf('%s/%d.jpg', $this->picturePath, 'online_' . $online->online_id), 90, Image::JPEG);*/
        }
    }

    /**
     * Odstraní článek s danou ID.
     * @param string $id ID článku
     */
    public function removeOnline(string $id)
    {
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $id)->delete();
    }
}