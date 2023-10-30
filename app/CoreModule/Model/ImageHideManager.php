<?php

namespace App\CoreModule\Model;

use App\Model\DatabaseManager;
use Nette\Database\Explorer;
use Nette\Database\Table\Selection;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\ArrayHash;

/**
 * Správce recenzí na produkty.
 * @package App\EshopModule\Model
 */
class ImageHideManager extends DatabaseManager
{
    /** Konstanty pro manipulaci s modelem. */
    const
        TABLE_NAME = 'imagehide',
        COLUMN_ID = 'imghide_id',
        COLUMN_BLOG_ID = 'blog_id',
        COLUMN_NUMBER = 'number',
        COLUMN_HIDDEN = 'hidden';

    public function getBlogImageHides($blogId)
    {
        return $this->database->table(self::TABLE_NAME)
            ->where(BlogManager::COLUMN_ID, $blogId)
            ->order(self::COLUMN_ID . ' DESC');
    }

    /**
     * Uloží nastavení obrázku blogu.
     * @param int $blogId  ID produktu
     * @param int $imageIndex index obrázku (0 je první)
     */
    public function saveBlogImage($blogId, $imageIndex, $hide = null)
    {
        $rows[] = array(
            self::COLUMN_BLOG_ID => $blogId,
            self::COLUMN_NUMBER => $imageIndex,
            self::COLUMN_HIDDEN => true,
        );    
        $this->database->table(self::TABLE_NAME)->insert($rows);
    }    
       
    /**
     * Upraví skrztí obrázku produktu.
     * @param int $blogId  ID produktu
     * @param int $imageIndex index obrázku (0 je první)
     * 
     * bude muset být napsané stylem save - nejprv uloží a pokud id a index existuje přepíše hodn hide !!!
     */
    public function hideBlogImage($blogId, $imageIndex, bool $hide = null)
    {
        $this->database->table(self::TABLE_NAME)
			->where(self::COLUMN_BLOG_ID, $blogId)->where(self::COLUMN_NUMBER, $imageIndex)
            ->update(array(self::COLUMN_HIDDEN => $hide));        
    }

    /**
     * Upraví skrztí obrázku produktu.
     * @param int $blogId  ID produktu
     * @param int $imageIndex index obrázku (0 je první)
     * 
     * bude muset být napsané stylem save - nejprv uloží a pokud id a index existuje přepíše hodn hide !!!
     */
    public function switchHideBlogImage($blogId, $imageIndex, $newIndex)
    {
        $this->database->table(self::TABLE_NAME)
			->where(self::COLUMN_BLOG_ID, $blogId)->where(self::COLUMN_NUMBER, $imageIndex)
            ->update(array(self::COLUMN_NUMBER => $newIndex));        
    }

    /**
     * Výpis hodn. hidden řádku jedné fotky - handle hide pic
     */
    
    public function getHiddenImage($blogId, $imageIndex)
	{
		return $this->database->table(self::TABLE_NAME)->select(self::COLUMN_HIDDEN)->where(self::COLUMN_BLOG_ID, $blogId)->where(self::COLUMN_NUMBER, $imageIndex)->fetchField(self::COLUMN_HIDDEN);
	}
    
    /**
     * Výpis řádku jedn. fotky podle in_blog i indexu obr. - v presenteru blogu ověřuje jestli daný řádek existuje
     * nešlo by přenést DB dotaz do presenteru? !!!
     */
     
    public function getImageIdindex($blogId, $index, $columns = null)
    {
        return $this->database->table(self::TABLE_NAME)
            ->select($columns ? $columns : '*')
            ->where(self::COLUMN_BLOG_ID, $blogId)->where(self::COLUMN_NUMBER, $index)->fetch();
    }
    
    /** všechny řádky ke konkr. blogId */
    
    public function getImageFromId($blogId, $columns = null)
    {
        $pictures = $this->database->table(self::TABLE_NAME)
    		->where(self::COLUMN_BLOG_ID, $blogId);
        return $pictures;
    }
    
    public function getImageHiddenFromId($blogId, $imagesCount, $columns = null)
    {
        $pichiddendatas = $this->database->table(self::TABLE_NAME)
    		->where(self::COLUMN_BLOG_ID, $blogId)
            ->where(self::COLUMN_HIDDEN);

        $picdata = array();
        for ($i = 0; $i < $imagesCount; $i++)
        $picdata[$i] = 0;
        
        foreach ($pichiddendatas as $pichiddendata) {
            if ($pichiddendata->hidden) {
                // ???Položku přidáme do nového stromu a rekurzivně přidáme strom podpoložek
                $picdata[$pichiddendata->number] = 1;
            }
        }
        
        //pole o počtu obr/imagesCount/ konkr. blogu s hodn 1 u skrytých obr
        return $picdata;
    }
}