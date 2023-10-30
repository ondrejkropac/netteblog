<?php

namespace App\CoreModule\components;

use App\CoreModule\Model\GalleryManager;
use Nette\Application\UI\Control;
use Nette\Database\Table\IRow;

/**
 * Komponenta pro zobrazování a manipulaci s náhledy produktu.
 * @package App\CoreModule\Components
 */
class GalleryImagesControl extends Control
{
    /** Cesta k souboru šablony pro tuto komponentu. */
    const TEMPLATE = __DIR__ . '/../templates/components/galleryImages.latte';

    /** @var GalleryManager Instance třídy modelu pro práci s produkty. */
    private GalleryManager $galleryManager;

    /** @var null|string URL produktu, pro který se má komponenta vykreslit, nebo null pro nový produkt. */
    private $galleryId = null;

    /** @var int Počet obrázků u daného produktu nebo nula pro nový produkt. */
    private int $imagesCount = 0;

    /**
     * Konstruktor komponenty s modelem pro práci s produkty.
     * @param GalleryManager $galleryManager třída modelu pro práci s produkty předávaná standardně v rámci presenteru
     */
    public function __construct(GalleryManager $galleryManager)
    {
        $this->galleryManager = $galleryManager;
    }

    /**
     * Setter pro produkt.
     * @param bool|mixed|IRow $bloggalerie bloggalerie, pro který se má komponenta vykreslit
     */
    public function setGallery($gallery)
    {
        $this->galleryId = $gallery->gallery_id;//dump($this->bloggalerieUrl);
        //$this->database->table('my_table')->count('*');
        //dump($this->galleryManager->rowCount());
        $galleries = $this->galleryManager->getAllGalleries(); //dump($galleries);
        //$this->imagesCount = $this->$galleries->getRowCount(); // imagesCount !!!
    }

    /**
     * Vykresluje komponentu pro nastavený produkt.
     */
    public function render()
    {
        $this->template->setFile(self::TEMPLATE); // Nastaví šablonu komponenty.
        // Předává parametry do šablony.
        $this->template->galleryId = $this->galleryId;
        $this->template->imagesCount = $this->imagesCount;
        echo 'Pozor je zavolaná komponenta GalleryImages';
        $this->template->render(); // Vykreslí komponentu.
    }

    /**
     * Signál pro odstranění náhledu produktu.
     * @param string $url    URL produktu
     * @param int $index index náhledu (0 je první)
     */
    public function handleDeleteImage($url, $index)
    {
        $this->galleryManager->removeGalleryImage($id, $index);
        $presenter = $this->getPresenter();
        if ($presenter->isAjax()) {
            $this->imagesCount--;
            $this->redrawControl();
        } else $presenter->redirect('this');
    }
}