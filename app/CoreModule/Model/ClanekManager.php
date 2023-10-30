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
use Nette\Utils\FileSystem;

/**
 * Model pro správu článků v redakčním systému.
 * @package App\CoreModule\Model
 */
class ClanekManager extends DatabaseManager
{
    /** @var string */
    private $picturePath;

    /** Konstanty pro práci s databází. */
    const
        TABLE_NAME = 'clanky',
        TABLE_ARCHYV_NAME = 'clanky_archyv',
        TABLE_CATEGORY = 'category',
        COLUMN_ID = 'clanky_id',
        COLUMN_STAV = 'stav',
        COLUMN_CATEGORY = 'category',
        COLUMN_AUTOR = 'autor',
        COLUMN_URL = 'url',
        COLUMN_APPROVE = 'approve',
        COLUMN_GALERIE = 'galerie',
        COLUMN_PUBLISH = 'publikace',
        COLUMN_VIEWS = 'viewsCount',
        COLUMN_IMAGE_COUNT = 'imagesCount',
        COLUMN_TITLE = 'titulek';

    // Konstanty pro manipulaci s obrázky.
    /** Velikost miniatury obrázku produktu. */
    const THUMB_SIZE = 520;

    /** Velikost obrázku. */
    const IMAGE_SIZE = 800;

    //const CLANEKGAL_IMAGES_PATH = '';

    public function __construct(Context $database, string $picturePath) {
        parent::__construct($database);
        $this->picturePath = $picturePath;
    }

    /**
     * Vrátí seznam všech článků v databázi seřazený sestupně nebo vzestupně podle filtru.
     * @return Selection seznam všech článků
     */

    //implementace: pokud je findAll - vypiš i s neschválenými else jen schválené...
    //              pokud je old - vypiš vzestupně, jinak sestupně

    public function getAllClanky($old=null, $findAll=null): Selection {
        if ($old == 1) $old = '';
        if ($findAll) {$data = $this->database->table(self::TABLE_NAME)->order(self::COLUMN_ID . $old);}
        else {$data = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_APPROVE, true)->order(self::COLUMN_ID . $old);}
        return $data;
    }

    /**
     * Vrátí seznam všech článků v databázi seřazený sestupně nebo vzestupně podle filtru a kategorie.
     * @return Selection seznam všech článků
     */

    public function getClankyKategorie(array $parameters): Selection {
        // pokud kategorie /categoryFilter/...$parameters['category_url'] nebude vypněna chybně vypíše všechny články i neschválené! -> review!
        $clanky = $this->database->table(self::TABLE_NAME);
    
        // Filtrování podle kategorie.
        if (!empty($parameters['category_url']))

        if (!$parameters['onlyapprove'])
            $clanky->where(self::COLUMN_CATEGORY,
                    $parameters['category_url']
            )->order(self::COLUMN_ID . $parameters['filter']);
        else
            $clanky->where(self::COLUMN_CATEGORY,
                    $parameters['category_url']
            )->where(self::COLUMN_APPROVE, true)
            ->order(self::COLUMN_ID . $parameters['filter']);
    
        return $clanky;
    }

    /**
     * Vrátí seznam všech článků v databázi seřazený sestupně nebo vzestupně podle filtru a kategorie.
     * @return Selection seznam všech článků
     */

    public function getClankyAutora($parameters): Selection {
        $clanky = $this->database->table(self::TABLE_NAME);
    
        // Filtrování podle kategorie.
        if (!empty($parameters))
            $clanky->where(self::COLUMN_AUTOR,
                    $parameters
            )->order(self::COLUMN_ID . ' DESC');
    
        return $clanky;
    }

    /**
     * Počet záznamů v tabulce dle kategorie.
     */
    public function vratPocetClankuKategorie(string $url, $logedin)
    {
        if ($logedin) return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_CATEGORY, $url)->count('*');
        else return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_APPROVE, true)->where(self::COLUMN_CATEGORY, $url)->count('*');
    }

    /**
     * Vrátí URL článků. Paginace
     * @return array URL článků.
     */
    public function getClankyAllUrl()
    {
            return $this->database->table(self::TABLE_NAME)->select(self::COLUMN_URL)->fetchPairs(null, self::COLUMN_URL);
    }

    /**
     * Vrátí TITLE článků. Paginace
     * @return array TITLE článků.
     */
    public function getClankyUrl($approve = null)
    {
            //self::COLUMN_URL ještě rozhodni jestli URL nebo Titulek
            if ($approve) {$data = $this->database->table(self::TABLE_NAME)->select(self::COLUMN_TITLE)->where(self::COLUMN_APPROVE, $approve)->fetchPairs(null, self::COLUMN_TITLE);}
            else {$data = $this->database->table(self::TABLE_NAME)->select(self::COLUMN_TITLE)->fetchPairs(null, self::COLUMN_TITLE);}
        return $data;
    }

    /**
     * Vrátí ID článků. Pro listování filtrování schválených článků podle přihlášení
     * @return array ID článků
     */
    public function getClankyId($approve = null)
    {
        if ($approve) {$dataid = $this->database->table(self::TABLE_NAME)->select(self::COLUMN_ID)->where(self::COLUMN_APPROVE, $approve)->fetchPairs(null, self::COLUMN_ID);}
            else {$dataid = $this->database->table(self::TABLE_NAME)->select(self::COLUMN_ID)->fetchPairs(null, self::COLUMN_ID);}
        return $dataid;
    }

    public function getClankyPosledni($onlyapprove) // pro base presenter
    {       
        if (!$onlyapprove) return $this->database->table(self::TABLE_NAME)->order(self::COLUMN_ID . ' DESC')->fetch();//poslední
        else return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_APPROVE, true)->order(self::COLUMN_ID . ' DESC')->fetch();
    }

	public function getClanekFromId(int $id, string $columns = NULL): IRow {
		return $this->database->table(self::TABLE_NAME)
			->select($columns ? $columns : '*')
			->where(self::COLUMN_ID, $id)
			->fetch();
	}
    
    //! v presenteru nepoužito!
	public function getClanekFromUrl(string $url, string $columns = NULL): IRow {
		return $this->database->table(self::TABLE_NAME)
			->select($columns ? $columns : '*')
			->where(self::COLUMN_URL, $url)
			->fetch();
	}

    /**
     * Vrátí článek z databáze podle jeho URL.
     * @param string $url URl článku
     * @return false|ActiveRow první článek, který odpovídá URL nebo false pokud článek s danou URL neexistuje
     */
    public function getClanek($url)
    {
        return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_URL, $url)->fetch();
    }

    /**
     * Uloží článek do systému.
     * Pokud není nastaveno ID vloží nový článek, jinak provede editaci článku s daným ID.
     * @param array|ArrayHash $clanek článek
     */
    public function saveClanek(ArrayHash $values){

        $clanekData = $values;
        //podm9nka na existenci
        if (isset($values['picture'])){
        $pic = $values['picture'];
        unset($clanekData['picture']);
        }

        if (empty($values[self::COLUMN_ID])) {
            unset($values[self::COLUMN_ID]);
            $clanek = $this->database->table(self::TABLE_NAME)->insert($clanekData);
        } else {
            $clanek = $this->database->table(self::TABLE_NAME) 
                        ->wherePrimary($values[self::COLUMN_ID]);

                        $clanek->update($clanekData);
                        $clanek = $clanek->fetch();
        }

        if (!empty($pic) && $pic->isOk()) {
            /** @var Image $im */
            $im = $pic->toImage();
            //$im->resize(900, 400, Image::EXACT);
            $im->resize(null, self::THUMB_SIZE);
            $im->save('images/image_' . $clanek->clanky_id . '.jpg', 90, Image::JPEG); // cestu $this->picturePath dočasně nastavuju na images
            //$im->save('images/image_' . $clanek->clanky_id . '.jpg', null, Image::PNG);
            //$im->save(sprintf('%s/%d.jpg', 'images', 'image_' . $clanek->clanky_id), 90, Image::JPEG); // cestu $this->picturePath dočasně nastavuju na images
        }
    }

    public function getArchyveClanky(array $parameters=null): Selection {
        $clankyArchyv = $this->database->table(self::TABLE_ARCHYV_NAME);
    
        // Filtrování podle kategorie. & Nepoužito dokud nevyhledáv8 víc kategorií pro jeden čl pak musí bát implementována tabulka čl-kat
        if (!empty($parameters['category_id']))
            $clankyArchyv->where(':' . self::TABLE_NAME . '_' . CategoryManager::TABLE_NAME .
                    '.' . CategoryManager::TABLE_NAME .
                    '.' . CategoryManager::COLUMN_ID,
                    $parameters['category_id']
            );
    
        return $clankyArchyv;
    }

    /**
     * Archyvuje článek do systému.
     * Pokud není nastaveno ID vloží nový článek, jinak provede editaci článku s daným ID.
     * @param array|ArrayHash $clanek článek
     */
    public function saveArchyveClanek($values){

        //if (empty($values[self::COLUMN_ID])) {
            unset($values[self::COLUMN_ID]);
            $clanek = $this->database->table(self::TABLE_ARCHYV_NAME)->insert($values);
        /*}*/
    }

    public function saveClanekCategory($values){
        $clanek = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_URL, $values[self::COLUMN_URL])->update($values);
        //return $clanek;
    }

    public function saveClanekAutor($values){
        $clanek = $this->database->table(self::TABLE_NAME)->where(self::COLUMN_URL, $values[self::COLUMN_URL])->update($values);
        //return $clanek;
    }
    
    /**
     * Nahraje obrázky k danému produktu.
     * @param string $clanekUrl         URL produktu, ke kterému nahráváme obrázky
     * @param Image[] $images        obrázky
     * @param null|string $oldBloggalUrl URL staré verze produktu (každá editace vytvoří nový produkt)
     */
    public function saveClanekgalerieImages($clanekUrl, array $images, $oldImagesCount = null) // $oldBloggalImagesCount= null
    {
        $imagesCount = $this->saveProductImages($clanekUrl, $images, $oldImagesCount);//  $oldBloggalUrl je jednoduchý sygnál do productImageManager->saveProductImages že budu přejmenovávat id! pro původní id produktu!
        $this->database->table(self::TABLE_NAME)
            ->where(self::COLUMN_URL, array($clanekUrl))
            ->update(array(self::COLUMN_IMAGE_COUNT => $imagesCount));
    }

    /**
     * Uloží obrázky k danému produktu.
     * @param string $itemUrl         URL produktu, ke kterému ukládáme obrázky
     * @param Image[] $images        obrázky
     * @param int $imagesCount       počet obrázků u produktu
     * @return int nový počet obrázků produktu (staré + nově uložené)
     */
    public function saveProductImages($itemUrl, array $images, /*$oldItemUrl = null,*/ $imagesCount = 0) // třetí parametr aktuálně předává počet obr před editací - public function saveBloggalerieImages($bloggalUrl, array $images, $oldBloggalImagesCount= null) - z BlogGalerieManager
    {       
        $clanek_id = $this->getClanekFromUrl($itemUrl, self::COLUMN_ID)[self::COLUMN_ID];
        $imagePath = 'images/clanky/';
        FileSystem::createDir($imagePath);

            // Nahraje další obrázky k produktu.
            foreach ($images as $image) {
                // První obrázek uložíme i jako miniaturu.
                if (!$imagesCount) {
                    $thumb = clone $image;
                    $thumb->resize(null, self::THUMB_SIZE);
                    $thumb->save($imagePath . $itemUrl . '_thumb.png', null, Image::PNG);//$this->CLANEKGAL_IMAGES_PATH
                    
                    $index = clone $image;
                    $index->resize(900, 400, Image::EXACT);
                    $index->save($imagePath . $clanek_id . '.jpg', 90, Image::JPEG);//$this->CLANEKGAL_IMAGES_PATH
                    //$thumb->save($imagePath . $itemUrl . '_thumb.png', 90, Image::JPEG);
                }
                $image->resize(null, self::IMAGE_SIZE);
                $image->save($imagePath . 'clanek_' . $clanek_id . '/clanek_' . $itemUrl . '_' . $imagesCount . '.jpg', null, Image::JPEG);//$this->CLANEKGAL_IMAGES_PATH
                $imagesCount++;
            }
            return $imagesCount;
    }

    /**
     * Odstraní článek s danou URL.
     * @param string $url URL článku
     */
    public function removeClanek(string $url)
    {
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_URL, $url)->delete();
    }

    /**
     * Změní článeku s danou URL hodnotu approve.
     * @param string $url URL článku
     */
    public function approveClanek(string $url, $pub = null, $publikace = null)
    {
        //$updateData = array(self::COLUMN_APPROVE => $pub, self::COLUMN_PUBLISH => $publikace);
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_URL, array($url))->update(array(self::COLUMN_APPROVE => $pub, self::COLUMN_PUBLISH => $publikace));
    }

    /**
     * Odstraní článek s danou URL.
     * @param string $url URL článku
     */
    public function countViewClanek(string $url)
    {
        $this->database->table(self::TABLE_NAME)->where(self::COLUMN_URL, $url)->update([self::COLUMN_VIEWS => Explorer::literal(self::COLUMN_VIEWS . '+1')]);
    }


    /**
     * Vrátí seznam všech kategorií v databázi seřazený sestupně od naposledy přidané.
     * @return Selection seznam všech kategorií
     */
    public function getKategorie()
    {
        return $this->database->table(self::TABLE_CATEGORY)->order('id' . ' DESC');
    }

    public function getPublish($Url)
	{
		return $this->database->table(self::TABLE_NAME)->select(self::COLUMN_APPROVE)->where(self::COLUMN_URL, $Url)->fetchField(self::COLUMN_APPROVE);
	}

    /**
     * Vrátí seznam všech článků v databázi podle hodnoty definující schválený/neschválený seřazený sestupně od naposledy přidaného.
     * @return Selection seznam všech článků
     *
     * @param int $approve Hodnota publikováno~approve = TRUE článku
     */

    public function getApproveClanky($approve): Selection {
        return $this->database->table(self::TABLE_NAME)->where (self::COLUMN_APPROVE, $approve)->order(self::COLUMN_ID . ' DESC');
    }
    
    public function getNonApproveClanky(): Selection {
        return $this->database->table(self::TABLE_NAME)->where (self::COLUMN_APPROVE, -1)->order(self::COLUMN_ID . ' DESC');
    }

	/**
	 * Vrátí seznam článků v databázi pouze s datem schválení !!! pro potřeby výpisu podle měsíců
	 * @return array Základní informace o všech článcích jako numerické pole asociativních polí
	 */
	public function getClankyPub($onlyapprove) // optimalizovaný na načtení minima informací
	{
        if (!$onlyapprove) return $this->database->table(self::TABLE_NAME)->select('publikace')->select('url')->select('clanky_id')->order('publikace' . ' DESC');
        else return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_APPROVE, true)->select('publikace')->select('url')->select('clanky_id')->order('publikace' . ' DESC'); //
        //return $this->database->table(self::TABLE_NAME)->fetchPairs('publikace', self::COLUMN_URL);
    }

    public function getClankyViews($onlyapprove): Selection {
        
        // vrací vše při přihlášení jinak jen schválené
        if (!$onlyapprove) return $this->database->table(self::TABLE_NAME)->order('viewsCount' . ' DESC');
        else return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_APPROVE, true)->order('viewsCount' . ' DESC');
    }
    
    /**
     * Odstraní obrázek.
     * @param string $itemUrl  URL produktu
     * @param int $imageIndex index obrázku (0 je první)
     */
    public function removeImage($id, $itemUrl, $imageIndex, $imagesCount)
    {
        $path = ('images/clanky/clanek_' . $id . '/clanek_' . $itemUrl . '_' . $imageIndex . '.jpg');
        if (file_exists($path)) unlink($path);
        // po smazání obrázku zmenši jejich počet v DB
        $imagesCount--;
        $this->database->table(self::TABLE_NAME)
            ->where(self::COLUMN_URL, $itemUrl)
            ->update(array(self::COLUMN_IMAGE_COUNT => $imagesCount));
    }
    
    /**
     * Vrátí seznam všech článků v databázi seřazený sestupně od naposledy přidané.
     * @return Selection seznam všech kategorií
     */
    public function getUnpubClankyName($approve)
    {
        return $this->database->table(self::TABLE_NAME)->where(self::COLUMN_APPROVE, $approve)->fetchPairs(self::COLUMN_URL, self::COLUMN_TITLE);
    }
}