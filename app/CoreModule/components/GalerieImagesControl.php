<?php

namespace App\CoreModule\components;

use App\CoreModule\Model\BlogGalerieManager;
use App\CoreModule\Model\ProductImageManager;
use App\CoreModule\Model\ImageAltManager;
use App\CoreModule\Model\ImageHideManager;
use Nette\Application\UI\Control;
use Nette\Database\Table\IRow;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

/**
 * Komponenta pro zobrazování a manipulaci s náhledy produktu.
 * @package App\CoreModule\Components
 */
class GalerieImagesControl extends Control
{
    /** Cesta k souboru šablony pro tuto komponentu. */
    const TEMPLATE = __DIR__ . '/../templates/components/galerieImages.latte';

    /** @var BlogGalerieManager Instance třídy modelu pro práci s produkty. */
    private BlogGalerieManager $bloggalerieManager;
    
    /** @var ProductImageManager Instance třídy modelu pro práci s obrázky produktů. */
    private ProductImageManager $productimageManager;

    /** @var ImageAltManager */
    private ImageAltManager $imagealtManager;
    private ImageHideManager $imagehideManager;

    /** @var null|string URL produktu, pro který se má komponenta vykreslit, nebo null pro nový produkt. */
    private $bloggalerieUrl = null;private $blogId = null;

    /** @var int Počet obrázků u daného produktu nebo nula pro nový produkt. */
    private int $imagesCount = 0;

    /**
     * Konstruktor komponenty s modelem pro práci s produkty.
     * @param BlogGalerieManager $bloggalerieManager třída modelu pro práci s produkty předávaná standardně v rámci presenteru
     * @param ImageAltManager $imagealtManager třída modelu pro práci s produkty předávaná standardně v rámci presenteru
     */
    public function __construct(BlogGalerieManager $bloggalerieManager, ProductImageManager $productimageManager, ImageAltManager $imagealtManager, ImageHideManager $imagehideManager)
    {
        $this->bloggalerieManager = $bloggalerieManager;
        $this->productimageManager = $productimageManager;
        $this->imagealtManager = $imagealtManager;
        $this->imagehideManager = $imagehideManager;
    }

    /**
     * Setter pro produkt.
     * @param bool|mixed|IRow $bloggalerie bloggalerie, pro který se má komponenta vykreslit
     */
    public function setBlogGalerie($bloggalerie)
    {
        $this->bloggalerieUrl = $bloggalerie->url;
        $this->blogId = $bloggalerie->bloggal_id;
        $this->imagesCount = $bloggalerie->imagesCount;
        if (!($this->imagealtManager->getImageFromId($bloggalerie->bloggal_id)))
            $this->flashMessage('Záznamy ještě nejsou.');
        else{
            if ($imagealt = $this->imagealtManager->getImageDescFromId($bloggalerie->bloggal_id, $bloggalerie->imagesCount))
            // Předání parametrů do šablony.
            $this->template->imagealt = $imagealt;
        }

        if (!($this->imagehideManager->getImageFromId($bloggalerie->bloggal_id)))
            $this->flashMessage('Záznamy ještě nejsou.');
        else{
            if ($imagehide = $this->imagehideManager->getImageHiddenFromId($bloggalerie->bloggal_id, $bloggalerie->imagesCount))
            // Předání parametrů do šablony.
            $this->template->imagehide = $imagehide;
        }
    }

    /**
     * Vykresluje komponentu pro nastavený produkt.
     */
    public function render()
    {
        $this->template->setFile(self::TEMPLATE); // Nastaví šablonu komponenty.
        // Předává parametry do šablony.
        $this->template->bloggalerieUrl = $this->bloggalerieUrl;
        $this->template->blogId = $this->blogId;
        $this->template->imagesCount = $this->imagesCount;
        $this->template->render(); // Vykreslí komponentu.
    }

    /**
     * Signál pro odstranění náhledu produktu.
     * @param string $url    URL produktu
     * @param int $index index náhledu (0 je první)
     */
    public function handleDeleteImage($url, $index)
    {
        $this->bloggalerieManager->removeBlogGalerieImage($url, $index);
        $presenter = $this->getPresenter();
        if ($presenter->isAjax()) {
            $this->imagesCount--;
            $this->redrawControl();
        } else $presenter->redirect('this');
    }
 
    /**
     * Signál pro záměnu sousedních náhledů produktu.
     * @param string $url    URL produktu
     * @param int $index index náhledu (0 je první)
     */
    public function handleSwitchImage($bloggalUrl, $imageIndex)
    {
        $this->bloggalerieManager->switchBlogGalerieImage($bloggalUrl, $imageIndex);
        $presenter = $this->getPresenter();
        if ($presenter->isAjax()) {
            $this->redrawControl();
        } else $presenter->redirect('this');
    }

    /**
     *  tlačítko v šabloně detail na skrytí jedné fotky
     */
        
    public function handleHideImage($id, $index)
    {
        if (empty($imagehide = $this->imagehideManager->getImageIdindex($id, $index))){
				$this->flashMessage('Záznam uložen.');
                //fotografie bez záznamu
                $this->imagehideManager->saveBlogImage($id, $index);
			} else { 
                if ($hidden = $this->imagehideManager->getHiddenImage($id, $index)) {
				$hide = false;
                } else {
                $hide = true;
                }
                //update!!!
                $this->imagehideManager->hideBlogImage($id, $index, $hide);
            }
    
        $this->redirect('this'); // Přesměruje do galerie.
    }

    //handle pro otevření formu popisu obrázků
    /**
     * tlačítko v šabloně manage na popis jedné fotky
     */
        
    public function handleDescImage($id, $index, $content, $editindex = 0)
    {
        if (empty($imagealtdata = $this->imagealtManager->getImageIdindex($id, $index))){
            $imagealtdata['number'] =  $index;
            $imagealtdata['blog_id'] =  $id;
        }

        $editindex = $index + 1;
        $this->template->number = $editindex;//číslo obrázku
        $this->template->content = $content;//popis daného obrázku
        $this['imagealtForm']->setDefaults($imagealtdata);
    }
    
    /**
     * Vytváří a vrací formulář pro editaci článků.
     * @return Form formulář pro editaci článků
     */
    protected function createComponentImagealtForm()
    {
        // Vytvoření formuláře a definice jeho polí.
        $form = new Form;
        $form->addHidden('blog_id');
        $form->addHidden('number');
        $form->addText('content', 'Titulek')->setRequired();
          $form->addSubmit('send', 'Uložit článek');

        $form->onSuccess[] = function (Form $form, ArrayHash $values) {

            try {
                $this->imagealtManager->addImageAlt($values);
                $this->flashMessage('Článek byl úspěšně uložen.');
                $this->redirect('this');
            } catch (UniqueConstraintViolationException $e) {
                $this->flashMessage('Článek s touto URL adresou již existuje.');
            }
        };
        return $form;
    }
}