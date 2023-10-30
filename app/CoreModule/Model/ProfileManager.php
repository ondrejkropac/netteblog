<?php

declare(strict_types=1);

namespace App\CoreModule\Model;

use App\Model\DatabaseManager;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Utils\ArrayHash;
use Nette\Database\Context;
use Nette\Utils\Image;

//use Nette\Database\Table\IRow;
//use Nette\Database\Context;
//use Nette\Utils\Image;
//use Nette\SmartObject;
//use Nette\Utils\FileSystem;
//use Nette\Utils\UnknownImageFileException;

/**
 * Model pro správu článků v redakčním systému.
 * @package App\CoreModule\Model
 */
class ProfileManager extends DatabaseManager
{
    /** Konstanty pro práci s databází. */
    const
        TABLE_NAME = 'profiles',
        COLUMN_ID = 'profile_id';

    /** @var string */
    private $picturePath;

    public function __construct(Context $database, string $picturePath) {
        parent::__construct($database);
        $this->picturePath = $picturePath;
    }

    /**
     * Vrátí seznam všech článků v databázi seřazený sestupně od naposledy přidaného.
     * @return Selection seznam všech článků
     */
    public function getProfiles()
    {
        return $this->database->table(self::TABLE_NAME);//->order(self::COLUMN_ID . ' DESC')
    }

    /**
     * Vrátí článek z databáze podle jeho ID.
     * @param string $id Id článku
     * @return false|ActiveRow první článek, který odpovídá ID nebo false pokud článek s danou ID neexistuje
     */
    public function getProfile($id)
    {
        return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $id)->fetch();
    }

    /**
     * Uloží článek do systému.
     * Pokud není nastaveno ID vloží nový článek, jinak provede editaci článku s daným ID.
     * @param array|ArrayHash $profile článek
     */
    public function saveProfile(ArrayHash $profile)
    {
        /*if (empty($profile[self::COLUMN_ID])) {
            unset($profile[self::COLUMN_ID]);
            $this->database->table(self::TABLE_NAME)->insert($profile);
        } else
            $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $profile[self::COLUMN_ID])->update($profile);*/

            $pic = $profile['picture'];$picbike = $profile['picture_bike'];
            unset($profile['picture']);unset($profile['picture_bike']);
        
        if (empty($profile[self::COLUMN_ID])) {
            unset($profile[self::COLUMN_ID]);
            $profile = $this->database->table(self::TABLE_NAME)->insert($profile);
        } else
            $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $profile[self::COLUMN_ID])->update($profile);
        
        if (!empty($pic) && $pic->isOk()) {
            /** @var Image $im */
            $im = $pic->toImage();
            $im->resize(600, 400, Image::EXACT);
            $im->save('images/profile/profilovka_' . $profile->karta_id . '.jpg', 90, Image::JPEG); // cestu $this->picturePath dočasně nastavuju na images
        }
        if (!empty($picbike) && $picbike->isOk()) {
            /** @var Image $im */
            $im = $picbike->toImage();
            $im->resize(600, 400, Image::EXACT);
            $im->save('images/profile/profilovka_' . $profile->karta_id . '.jpg', 90, Image::JPEG); // cestu $this->picturePath dočasně nastavuju na images
        }
    }

    /**
     * Odstraní článek s danou ID.
     * @param string $id ID článku
     */
    public function removeProfile(string $id)
    {
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $id)->delete();
    }
}