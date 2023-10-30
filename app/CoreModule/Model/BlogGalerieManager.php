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
use Nette\SmartObject;
use Nette\Utils\FileSystem;

/**
 * Model pro správu článků v redakčním systému.
 * @package App\CoreModule\Model
 */

class BlogGalerieManager extends DatabaseManager
{
    /** @var string */
    private $picturePath;

    /** Konstanty pro práci s databází. */
    const
        TABLE_NAME = 'bloggal',
        TABLE_CATEGORY = 'category',
        COLUMN_ID = 'bloggal_id',
        COLUMN_CATEGORY = 'category',
        COLUMN_URL = 'url',
        COLUMN_IMAGE_COUNT = 'imagesCount',
        COLUMN_NOTES = 'notes',
        COLUMN_ALT_NOTES = 'altnotes',
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

    public function getBloggaleriesKategorie(array $parameters): Selection {
        $bloggaleries = $this->database->table(self::TABLE_NAME);
    
        // Filtrování podle kategorie.
        if (!empty($parameters['category_url']))
            $bloggaleries->where(self::COLUMN_CATEGORY,
                    $parameters['category_url']
            );
    
        return $bloggaleries;
    }

    /**
     * Vrátí URL článků.
     * @return array URL článků.
     */
    public function getBloggaleriesUrl()
    {
        return $this->database->table(self::TABLE_NAME)
            ->select(self::COLUMN_URL)
            ->fetchPairs(null, self::COLUMN_URL);//self::COLUMN_URL ještě rozhodni jestli URL nebo Titulek
    }
    //PSICHO KONSTRUKT PRO 
    /**
     * Vrátí URL článků.
     * @return array URL článků.
     */
    public function getBloggaleriesUrlSet()
    {
        return $this->database->table(self::TABLE_NAME)
            ->select(self::COLUMN_URL)
            ->fetchPairs(self::COLUMN_URL, self::COLUMN_URL);//self::COLUMN_URL ještě rozhodni jestli URL nebo Titulek
    }

    /**
     * Vrátí ID článků.
     * @return array ID článků
     */
    public function getBloggaleriesId()
    {
        return $this->database->table(self::TABLE_NAME)
            ->select(self::COLUMN_ID)
            ->fetchPairs(null, self::COLUMN_ID);
    }

    public function getAllBloggaleries(): Selection {
        return $this->database->table(self::TABLE_NAME);//->order(self::COLUMN_ID . ' DESC')
    }

	public function getBlogGalerieFromId(int $id, string $columns = NULL): IRow {
		return $this->database->table(self::TABLE_NAME)
			->select($columns ? $columns : '*')
			->where(self::COLUMN_ID, $id)
			->fetch();
	}
    
	public function getBlogGalerieFromUrl(string $url, string $columns = NULL): IRow {
		return $this->database->table(self::TABLE_NAME)
			->select($columns ? $columns : '*')
			->where(self::COLUMN_URL, $url)
			->fetch();
	}

    public function getBlogGalerieIdFromUrl(string $url): IRow {
		return $this->database->table(self::TABLE_NAME)
			//->select($columns ? $columns : '*')
            ->select(self::COLUMN_ID)
			->where(self::COLUMN_URL, $url)
			->fetch();
	}

    public function getLastBlogGalerii() // jen pro list~! 
    {
        return $this->database->table(self::TABLE_NAME)->order(self::COLUMN_ID . ' DESC')->fetch();//poslední
    }

    /**
     * Vrátí článek z databáze podle jeho URL.
     * @param string $url URl článku
     * @return false|ActiveRow první článek, který odpovídá URL nebo false pokud článek s danou URL neexistuje
     */
    public function getBlogGalerii($url)
    {
        return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_URL, $url)->fetch();
    }

    /**
     * Uloží článek do systému.
     * Pokud není nastaveno ID vloží nový článek, jinak provede editaci článku s daným ID.
     * @param array|ArrayHash $bloggalerie článek
     */
    public function saveBlogGalerie(ArrayHash $values){

        if (!empty($values['picture']) && $values['picture']->isOk()) {
            /** @var Image $im */
            $im = $values['picture']->toImage();
            $im->resize(900, 400, Image::EXACT);
            $im->save(sprintf('%s/%d.jpg', $this->picturePath, $values->bloggal_id), 90, Image::JPEG);
        }

        unset($values['picture']);
        $bloggalerieData = $values;

        if (empty($values[self::COLUMN_ID])) {
            unset($values[self::COLUMN_ID]);
            $this->database->table(self::TABLE_NAME)->insert($bloggalerieData);
        } else {
            $this->database->table(self::TABLE_NAME)->where(self::COLUMN_ID, $values[self::COLUMN_ID])->update($bloggalerieData);
        }
        return $values[self::COLUMN_URL];
    }

    /* IMAGES SECTION*/

    /**
     * Nahraje obrázky k danému produktu.
     * @param string $bloggalUrl         URL produktu, ke kterému nahráváme obrázky
     * @param Image[] $images        obrázky
     * @param null|string $oldBloggalUrl URL staré verze produktu (každá editace vytvoří nový produkt)
     */
    public function saveBloggalerieImages($bloggalUrl, array $images, $oldBloggalUrl = null)
    {
        $imagesCount = $this->productImageManager->saveProductImages($bloggalUrl, $images, $oldBloggalUrl, // DEL$oldBloggalImagesCount nahradilo původní - $oldBloggalUrl /ale vrácím do plvodního stavu/ a to je jednoduchý sygnál do productImageManager->saveProductImages že budu přejmenovávat id! pro původní id produktu!
        $oldBloggalUrl ? $this->getImagesCount($bloggalUrl) : 0);   
        $this->database->table(self::TABLE_NAME)
            ->where(self::COLUMN_URL, array($bloggalUrl))
            ->update(array(self::COLUMN_IMAGE_COUNT => $imagesCount));
    }

    /**
     * Odstraní obrázek produktu.
     * @param string $bloggalUrl  URL produktu
     * @param int $imageIndex index obrázku (0 je první)
     */
    public function removeBloggalerieImage($bloggalUrl, $imageIndex)
    {
        $imagesCount = $this->getImagesCount($bloggalUrl);
        $this->productImageManager->removeProductImage($bloggalUrl, $imageIndex, $imagesCount);
        $this->database->table(self::TABLE_NAME)
            ->where(self::COLUMN_URL, array($bloggalUrl))
            ->update(array(self::COLUMN_IMAGE_COUNT => ($imagesCount - 1)));
    }

    /**
     * Odstraní obrázek produktu.
     * @param string $bloggalUrl  URL produktu
     * @param int $imageIndex index obrázku (0 je první)
     */
    public function switchBloggalerieImage($bloggalUrl, $imageIndex)
    { //DELneměním počet img tak rouvnou do product(item)ImageManager!!!- to nešlo protože jsem tam nedokázal iniciovat blogal manager a metody pro ukládání notes /alt - bez jich by celá metoda mohlo odsud zmizet
        $clanek_id = $this->getBloggalerieFromUrl($bloggalUrl, self::COLUMN_ID)[self::COLUMN_ID];
        $blognotes = $this->database->table(self::TABLE_NAME)->select(self::COLUMN_NOTES)->where(self::COLUMN_URL, $bloggalUrl)->fetch();
        $altnotes = $this->getBlogGalerieFromUrl($bloggalUrl,(self::COLUMN_ALT_NOTES))[(self::COLUMN_ALT_NOTES)];
        $updateData = $this->productImageManager->switchProductImage($bloggalUrl, $imageIndex, $blognotes->notes, $altnotes, $clanek_id);//$imagesIndex-1, 
        if ($updateData) $this->saveImageData($bloggalUrl, $updateData);
    }

    /**
     * Změní hodnoty notes a alt s danou URL hodnotu approve.
     * @param string $url URL bloggalerie
     */
    public function saveImageData(string $url, $updateData)
    {
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_URL, array($url))->update(array(self::COLUMN_NOTES => $updateData['notes'], self::COLUMN_ALT_NOTES => $updateData['altnotes']));
    }

    /**
     * Vrací počet obrázků u daného produktu.
     * @param string $bloggalUrl URL produktu
     * @return int počet obrázků u daného produktu
     */
    private function getImagesCount($bloggalUrl)
    {
        return $this->getBloggalerieFromURL($bloggalUrl, self::COLUMN_IMAGE_COUNT)[self::COLUMN_IMAGE_COUNT];
    }

    /**
     * Odstraní článek s danou URL.
     * @param string $url URL článku
     */
    public function removeBlogGalerii(string $url)
    {
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_URL, $url)->delete();
    }
}