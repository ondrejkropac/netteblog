<?php

/*  _____ _______         _                      _
 * |_   _|__   __|       | |                    | |
 *   | |    | |_ __   ___| |___      _____  _ __| | __  ___ ____
 *   | |    | | '_ \ / _ \ __\ \ /\ / / _ \| '__| |/ / / __|_  /
 *  _| |_   | | | | |  __/ |_ \ V  V / (_) | |  |   < | (__ / /
 * |_____|  |_|_| |_|\___|\__| \_/\_/ \___/|_|  |_|\_(_)___/___|
 *                                _
 *              ___ ___ ___ _____|_|_ _ _____
 *             | . |  _| -_|     | | | |     |
 *             |  _|_| |___|_|_|_|_|___|_|_|_|
 *             |_|                          _ _ _        LICENCE
 *        ___ ___    ___    ___ ___ ___ ___| | |_|___ ___
 *       |   | . |  |___|  |  _| -_|_ -| -_| | | |   | . |
 *       |_|_|___|         |_| |___|___|___|_|_|_|_|_|_  |
 *                                                   |___|
 *
 * IT ZPRAVODAJSTVÍ  <>  PROGRAMOVÁNÍ  <>  HW A SW  <>  KOMUNITA
 *
 * Tento zdrojový kód je součástí výukových seriálů na
 * IT sociální síti WWW.ITNETWORK.CZ
 *
 * Kód spadá pod licenci prémiového obsahu s omezeným
 * přeprodáváním a vznikl díky podpoře našich členů. Je určen
 * pouze pro osobní užití a nesmí být šířen. Může být použit
 * v jednom uzavřeném komerčním projektu, pro širší využití je
 * dostupná licence Premium commercial. Více informací na
 * http://www.itnetwork.cz/licence
 */

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
class ImageAltManager extends DatabaseManager
{
    /** Konstanty pro manipulaci s modelem. */
    const
        TABLE_NAME = 'imagealt',
        COLUMN_ID = 'imgalt_id',
        COLUMN_BLOG_ID = 'blog_id',
        COLUMN_NUMBER = 'number',
        COLUMN_CONTENT = 'content';

    /**
     * Přidá k produktu recenzi.
     * @param array|ArrayHash $imgalt recenze
     * @throws UniqueConstraintViolationException Pokud se daný uživatel pokusí hodnotit stejný produkt vícekrát.
     */
    public function addImageAlt($imgalt) // nahrazeno save
    {
    	if (empty($this->getImageIdindex($imgalt->blog_id, $imgalt->number)))
        $this->database->table(self::TABLE_NAME)->insert($imgalt);
        else{
            $this->database->table(self::TABLE_NAME)
			->where(self::COLUMN_BLOG_ID, $imgalt->blog_id)->where(self::COLUMN_NUMBER, $imgalt->number)
            ->update(array(self::COLUMN_CONTENT => $imgalt->content));
        }
    }
        
    /**
     * Vloží popis obrázku produktu. // upload popisu!!!
     * @param int $blogId  ID produktu
     * @param int $imageIndex index obrázku (0 je první)
     */
    public function descBlogImage($blogId, $imageIndex, $content = null)
    {
        if ($this->getImageIdindex($blogId, $imageIndex)){
        $this->database->table(self::TABLE_NAME)
			->where(self::COLUMN_BLOG_ID, $blogId)->where(self::COLUMN_NUMBER, $imageIndex)
            ->update(array(self::COLUMN_CONTENT => $content['content']));
        } else {
            $blogImageData = [
                'content' => $content['content'],
                'blog_id' => $blogId,
                'number' => $imageIndex,
            ];
            $this->database->table(self::TABLE_NAME)->insert($blogImageData);
        }       
    }
    
    /**
     * Výpis řádku jedné fotky podle blog_id i indexu obr. - v presenteru blogu ověřuje jestli daný řádek existuje
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
        $picturedess = $this->database->table(self::TABLE_NAME)
    		->where(self::COLUMN_BLOG_ID, $blogId);
        return $picturedess;
    }
  
    public function getImageDescFromId($blogId, $imagesCount, $columns = null)
    {
        $picdescdatas = $this->database->table(self::TABLE_NAME)
    		->where(self::COLUMN_BLOG_ID, $blogId)
            ->where(self::COLUMN_CONTENT . ' IS NOT NULL');
            //->where('NOT ' . self::COLUMN_HIDDEN)
            
        $picdes = array();
        for ($i = 0; $i < $imagesCount; $i++)
        $picdes[$i] = 0;
        //prázdné pole o počtu obr/imagesCount/ konkr. blogu
        
        foreach ($picdescdatas as $picdescdata) {
            if ($description = $picdescdata->content) {
                // ???Položku přidáme do nového stromu a rekurzivně přidáme strom podpoložek
                $picdes[$picdescdata->number] = $description;
            }
        }

        //pole o počtu obr/imagesCount/ konkr. blogu s hodn 1 u skrytých obr
        return $picdes;
    } 
}

