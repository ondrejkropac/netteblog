<?php

namespace App\CoreModule\Model;

use Nette\SmartObject;
use Nette\Utils\Image;
use Nette\Utils\FileSystem;
use Nette\Utils\UnknownImageFileException;

/**
 * Správce obrázků produktů.
 * @package App\CoreModule\Model
 */
class ProductImageManager
{
    use SmartObject;

    // Konstanty pro manipulaci s obrázky.
    /** Velikost miniatury obrázku produktu. */
    const THUMB_SIZE = 320;

    /** Velikost obrázku. */
    const IMAGE_SIZE = 1800; // pohybuje kolem 200Kb na výšku 600kb na šířku

    /** Cesta k obrázkům produktů. */
    private string $BLOGGAL_IMAGES_PATH;
    
        /** @var BlogGalerieManager Instance třídy modelu pro práci s produkty. */
        private BlogGalerieManager $bloggalerieManager;


        /** @var ImageAltManager */
        private ImageAltManager $imagealtManager;
        private ImageHideManager $imagehideManager;

    /**
     * ProductImageManager constructor.
     * @param $imagePath string Cesta k ukládání obrázků.
     */
    public function __construct($imagePath, ImageAltManager $imagealtManager, ImageHideManager $imagehideManager)
    {
        $this->imagealtManager = $imagealtManager;
        $this->imagehideManager = $imagehideManager;

        // Vytvoření složky pro obrázky pokud neexistuje
        $this->BLOGGAL_IMAGES_PATH = $imagePath;
        FileSystem::createDir($this->BLOGGAL_IMAGES_PATH);
    }

    /**
     * Uloží obrázky k danému produktu.
     * @param string $itemUrl         URL produktu, ke kterému ukládáme obrázky
     * @param Image[] $images        obrázky
     * @param int $imagesCount       počet obrázků u produktu
     * @return int nový počet obrázků produktu (staré + nově uložené)
     */
    public function saveProductImages($itemUrl, array $images, $oldItemUrl = null, $imagesCount = 0) // třetí parametr aktuálně předává počet obr před editací - public function saveBloggalerieImages($bloggalUrl, array $images, $oldBloggalImagesCount= null) - z BlogGalerieManager
    {       
            // Přejmenování starých obrázků, pokud se změnilo URL produktu.
            if ($oldItemUrl != $itemUrl) $this->renameProductImages($oldItemUrl, $itemUrl, $imagesCount); // po refaktoringu a implementaci rename zalohovat obecnou třídu pro reimplementaci přejmenování Url/Id a poté tady smazat včetně parametru $oldItemUrl public function saveBloggalerieImages
            // Nahraje další obrázky k produktu.
            foreach ($images as $image) {
                // První obrázek uložíme i jako miniaturu.
                if (!$imagesCount) {
                    $thumb = clone $image;
                    $thumb->resize(null, self::THUMB_SIZE);
                    $thumb->save($this->BLOGGAL_IMAGES_PATH . $itemUrl . '_thumb.png', null, Image::PNG);
                }
                $image->resize(null, self::IMAGE_SIZE);
                $image->save($this->BLOGGAL_IMAGES_PATH . 'galerie_' . $itemUrl . '_' . $imagesCount . '.jpg', null, Image::JPEG);
                $imagesCount++;
            }
            return $imagesCount;
    }

    /**
     * Přejmenuje obrázky daného produktu tak, aby patřily jinému produktu.
     * @param string $oldItemUrl URL produktu, jehož obrázky chceme přesunout
     * @param string $itemUrl    URL produktu, ke kterému mají obrázky nově patřit
     * @param int $imagesCount  počet obrázků produktu
     */
    private function renameProductImages($oldItemUrl, $itemUrl, $imagesCount)
    {   
        // Přesun miniatury.
        $oldThumbnailPath = $this->BLOGGAL_IMAGES_PATH . $oldItemUrl . '_thumb.png';
        $newThumbnailPath = $this->BLOGGAL_IMAGES_PATH . $itemUrl . '_thumb.png';
        if (file_exists($oldThumbnailPath)) rename($oldThumbnailPath, $newThumbnailPath);
        // Přesun obrázků.
        for ($i = 0; $i < $imagesCount; $i++) {
            $oldPath = $this->BLOGGAL_IMAGES_PATH . 'galerie_' . $oldItemUrl . '_' . $i . '.jpg';
            $newPath = $this->BLOGGAL_IMAGES_PATH . 'galerie_' . $itemUrl . '_' . $i . '.jpg';
            if (file_exists($oldPath)) rename($oldPath, $newPath);
        }
    }

    /**
     * Odstraní obrázek produktu.
     * @param string $itemUrl   URL produktu
     * @param int $imageIndex  index obrázku (0 je první)
     * @param int $imagesCount počet obrázků produktu
     * @throws UnknownImageFileException Pokud soubor nebyl nalezen nebo se jedná o s
     */
    public function removeProductImage($itemUrl, $imageIndex, $imagesCount)
    {
        // Pokud je to první obrázek, mažeme i miniaturu.
        if ($imageIndex == 0) {
            $thumbnailPath = $this->removeThumb($itemUrl);
            // Snažíme se vytvořit novou miniaturu z druhého obrázku.
            $secondImagePath = $this->BLOGGAL_IMAGES_PATH . $itemUrl . '_1.jpg';
            if (file_exists($secondImagePath)) {
                $image = Image::fromFile($secondImagePath);
                $image->resize(null, self::THUMB_SIZE);
                $image->save($thumbnailPath, null, Image::PNG);
            }
        }
        // Mažeme obrázek.
        $this->removeImage($itemUrl, $imageIndex);
        // Přejmenování zbylých obrázků tak, aby šly za sebou.
        for ($i = $imageIndex + 1; $i < $imagesCount; $i++) {
            $oldPath = $this->BLOGGAL_IMAGES_PATH . $itemUrl . '_' . $i . '.jpg';
            $newPath = $this->BLOGGAL_IMAGES_PATH . $itemUrl . '_' . ($i - 1) . '.jpg';
            if (file_exists($oldPath)) rename($oldPath, $newPath);
        }
    }

    
    /**
     * Zamění pořadí obrázků produktu. stávající /$imageIndex/ a předchozí /i-1/
     * @param string $itemUrl   URL produktu
     * @param int $imageIndex  index obrázku (0 je první)
     * @throws UnknownImageFileException Pokud soubor nebyl nalezen nebo se jedná o s
     */
    public function switchProductImage($itemUrl, $in, $blognotes, $altnotes, $id)//$i=$imageIndex
    {
        // Zaměníme obrázky.

        $firstPath = $this->BLOGGAL_IMAGES_PATH . 'galerie_' . $itemUrl . '_' . $in. '.jpg';
        $pomocnyPath = $this->BLOGGAL_IMAGES_PATH . 'galerie_' . $itemUrl . '_pomocny.jpg';
        $secondPath = $this->BLOGGAL_IMAGES_PATH . 'galerie_' . $itemUrl . '_' . ($in - 1) . '.jpg';
        if (file_exists($firstPath)){
        rename($firstPath, $pomocnyPath);
        rename($secondPath, $firstPath);
        rename($pomocnyPath, $secondPath);
        }

        //poslední by mělo být duplicitní uložení hodnot imagehide a imgalt - a přejmenování na nějaké inteligentní názvy těchto proměnných!!! snad OK
        $hidden = $this->imagehideManager->getHiddenImage($id, $in);
        $hidden2 = $this->imagehideManager->getHiddenImage($id, $in-1);

        if ($hidden!=$hidden2){
            if ((!isset($hidden))||(!isset($hidden2))) {
                if (!$hidden) $this->imagehideManager->switchHideBlogImage($id, $in-1, $in);
                if (!$hidden2) $this->imagehideManager->switchHideBlogImage($id, $in, $in-1);
            }
            else{
                $hide = !$hidden;
                $this->imagehideManager->hideBlogImage($id, $in, $hide);
                $hide = !($hidden2);
                $this->imagehideManager->hideBlogImage($id, $in-1, $hide);
                //$this->imagehideManager->hideBlogImage($id, $in-1, !$hidden2);
            }
        }
        
        $imagealtn = $this->imagealtManager->getImageIdindex($id, $in);
        $imagealtn1 = $this->imagealtManager->getImageIdindex($id, $in-1);
        // v případě neexistence řádku-záznamu pro daný obrázek, založí prázdnou hodnotu pro následující insert v ImageAltManageru
        if(!isset($imagealtn)) $imagealtn['content'] = '';
        if(!isset($imagealtn1)) $imagealtn1['content'] = '';
        
        if (($imagealtn)||($imagealtn1)){
            $this->imagealtManager->descBlogImage($id, $in, $imagealtn1);
            $this->imagealtManager->descBlogImage($id, $in-1, $imagealtn);
        }
        
        //blognotes a altnotes je uložené přímo v tabulce blogal a jde o duplicitu ALT a HIDE v samostatných tabulkách ... později opustit!
        if (isset($blognotes)){
            if ($blognotes[$in]!=$blognotes[$in-1]){
            $blognotes[$in] = !($blognotes[$in]);
            $blognotes[$in-1] = !($blognotes[$in-1]);
            }
        }
        if (isset($altnotes)){
            //switch hodnoty alt v altnotes
            $polealt = explode(',', $altnotes);
            $prvni = $polealt[$in];
            $polealt[$in] = $polealt[($in-1)];
            $polealt[($in-1)] = $prvni;
            $altnotes = implode(',', $polealt);
            //předej zpět do gloggal manageru a ulož polealt a notes rychle save celý bloggal
            $updateData = array('altnotes' => $altnotes, 'notes' => $blognotes);
        }
        if ((isset($blognotes))||(isset($altnotes))) return $updateData;
   
    }

    /**
     * Odstraní miniaturu k produktu.
     * @param string $itemUrl URL produktu
     * @return string cestu k původní miniatuře
     */
    private function removeThumb($itemUrl)
    {
        $thumbnailPath = $this->BLOGGAL_IMAGES_PATH . $itemUrl . '_thumb.png';
        if (file_exists($thumbnailPath)) unlink($thumbnailPath);
        return $thumbnailPath;
    }
    /**
     * Odstraní obrázek.
     * @param string $itemUrl  URL produktu
     * @param int $imageIndex index obrázku (0 je první)
     */
    private function removeImage($itemUrl, $imageIndex)
    {
        $path = $this->BLOGGAL_IMAGES_PATH . $itemUrl . '_' . $imageIndex . '.jpg';
        if (file_exists($path)) unlink($path);
    }

    /**
     * Odstraní všechny obrázky produktu.
     * @param string $itemUrl   URL produktu
     * @param int $imagesCount počet obrázků produktu
     */
    public function removeProductImages($itemUrl, $imagesCount)
    {
        // Odstranění obrázků.
        for ($i = 0; $i < $imagesCount; $i++)
            $this->removeImage($itemUrl, $i);
        // Odstranění miniatury.
        $this->removeThumb($itemUrl);
    }
}