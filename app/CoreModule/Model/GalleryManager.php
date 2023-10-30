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
use Nette\Utils\Image;

/**
 * Model pro správu článků v redakčním systému.
 * @package App\CoreModule\Model
 */

class GalleryManager extends DatabaseManager
{
    /** @var string */
    private $picturePath;

    /** Konstanty pro práci s databází. */
    const
        TABLE_NAME = 'gallery',
        TABLE_CATEGORY = 'category',
        COLUMN_ID = 'gallery_id',
        COLUMN_URL = 'url',
        COLUMN_IMAGE_COUNT = 'imagesCount',
        COLUMN_PRF = 'portfolio',
        COLUMN_DEF = 'startPage',
        COLUMN_TITLE = 'titulek';

    /** @var ProductImageManager Instance třídy modelu pro práci s obrázky produktů. */
    private ProductImageManager $productImageManager;

    /**
     * Konstruktor s nastavením URL výchozího článku a injektovaným modelem pro správu článků.
     * @param Explorer $database                       automaticky injektovaná třída pro práci s databází
     * @param ProductImageManager $productImageManager automaticky injektovaná třída modelu pro práci s obrázky produktů
     */
    public function __construct(Context $database, ProductImageManager $productImageManager, string $picturePath)
    {
        parent::__construct($database);
        $this->productImageManager = $productImageManager;
        $this->picturePath = $picturePath;
    }
    
    /**
     * Vrátí seznam všech článků v databázi seřazený sestupně od naposledy přidaného.
     * @return Selection seznam všech článků
     */

    public function getAllGalleries(): Selection {
        return $this->database->table(self::TABLE_NAME)->order(self::COLUMN_ID . ' DESC');
    }

    /**
     * Vrátí seznam všech gallerií v databázi seřazený sestupně nebo vzestupně podle filtru.
     * @return Selection seznam všech článků
     */

    //implementace: pokud je all - vypiš i s neschválenými else jen skchválené...

    public function getAllGalleriesFilter($only=null, $old=' DESC'): Selection {
        //if ($old == 1) $old = '';
        if (!($only)) {$data = $this->database->table(self::TABLE_NAME)->order(self::COLUMN_ID . $old);}
        else {$data = $this->database->table(self::TABLE_NAME)->where($only, true)->order(self::COLUMN_ID . $old);}
        return $data;
    }

    /**
     * Vrátí článek z databáze podle jeho URL.
     * @param int $id ID článku
     * @return false|ActiveRow první článek, který odpovídá ID nebo false pokud článek s danou URL neexistuje
     */
    public function getGallery($id)
    {
        return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $id)->fetch();
    }
    
	public function getGalleryFromId(int $id, string $columns = NULL): IRow {
		return $this->database->table(self::TABLE_NAME)
			->select($columns ? $columns : '*')
			->where(self::COLUMN_ID, $id)
			->fetch();
	}

    /**
     * Uloží článek do systému.
     * Pokud není nastaveno ID vloží nový článek, jinak provede editaci článku s daným ID.
     * @param array|ArrayHash $gallery článek
     */
    public function saveGallery(ArrayHash $values){

        if (!empty($values['picture']) && $values['picture']->isOk()) {
            /** @var Image $im */
            $im = $values['picture']->toImage();
            //$im->resize(null, Image::EXACT);
            if (isset($values->orderGallery)) $order = $values->orderGalerie;
            else $order = null;
            $file = 'images/gallery/' . $_POST['imgFullNameGalerie'];
            if ((file_exists($file))) $file = 'images/gallery/' . $order+1 . '_' . $_POST['imgFullNameGalerie'];
            $im->save($file, 90, Image::JPEG); // cestu $this->picturePath dočasně nastavuju na images  . '.jpg'
            //$im->save('images/gallery/' . $values->imgFullNameGalerie, 90, Image::JPEG); // cestu $this->picturePath dočasně nastavuju na images  . '.jpg'
            //$im->save(sprintf('%s/%d.jpg', $this->picturePath, $values->imgFullNameGalerie), 90, Image::JPEG);
        }

        $pic = $values['picture'];
        unset($values['picture']);

        if (empty($values[self::COLUMN_ID])) {
            unset($values[self::COLUMN_ID]);
            $values = $this->database->table(self::TABLE_NAME)->insert($values);
        } else
            return $values[self::COLUMN_ID];

    }

    /**
     * Odstraní článek s danou ID.
     * @param string $id ID článku
     */
    public function removeGallery(int $id)
    {
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $id)->delete();
    }
    
    /* IMAGES SECTION*/

    /**
     * Přidá k obrázku zařazení.
     * @param array|ArrayHash $imgalt recenze
     * @throws UniqueConstraintViolationException Pokud se daný uživatel pokusí hodnotit stejný produkt vícekrát.
     */
    public function saveSetting($id, $set) // nahrazeno save
    {
            $this->database->table(self::TABLE_NAME)
			->where(self::COLUMN_ID, $id)
            ->update(array(key($set) =>  current($set)));
    }
    
    /**
     * Změní galerii s danou ID hodnotu portfolio.
     * @param int $id ID galerie
     */
    public function portfolGallery(int $id, $prf = null)
    {
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, array($id))->update(array(self::COLUMN_PRF => $prf));
    }

    /**
     * Změní galerii s danou ID hodnotu startPage.
     * @param int $id ID galerie
     */
    public function defaultGallery(int $id, $def = null)
    {
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, array($id))->update(array(self::COLUMN_DEF => $def));
    }

    public function getPortfol($Id)
	{
		return $this->database->table(self::TABLE_NAME)->select(self::COLUMN_PRF)->where(self::COLUMN_ID, $Id)->fetchField(self::COLUMN_PRF);
	}

    public function getDefault($Id)
	{
		return $this->database->table(self::TABLE_NAME)->select(self::COLUMN_DEF)->where(self::COLUMN_ID, $Id)->fetchField(self::COLUMN_PRF);
	}

    /**
     * Odstraní článek s danou URL.
     * @param string $url URL článku
     */
    public function removeBlogGalerii(string $url)
    {
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_URL, $url)->delete();
    }

    /**
     * Počet záznamů v tabulce.
     */
    public function rowCount()
    {
        $this->database->table(self::TABLE_NAME)->count('*');
    } 

    /**
     * Vrací počet obrázků u daného produktu.
     * @param string $bloggalUrl URL produktu
     * @return int počet obrázků u daného produktu
     */
    private function getImagesCount($bloggalUrl)
    {
        return $this->getGalleryFromURL($bloggalUrl, self::COLUMN_IMAGE_COUNT)[self::COLUMN_IMAGE_COUNT];
    }
}