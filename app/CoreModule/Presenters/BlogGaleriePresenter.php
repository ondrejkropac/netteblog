<?php

declare(strict_types=1);

namespace App\CoreModule\Presenters;

use App\CoreModule\Model\BlogGalerieManager;
use App\CoreModule\Model\CategoryManager;
use App\CoreModule\Model\ImageAltManager;
use App\CoreModule\Model\ImageHideManager;
use App\CoreModule\components\GalerieImagesControl;
use App\CoreModule\Model\ProductImageManager;
use App\Forms\BlogGalerieFormFactory;
use App\Presenters\BasePresenter;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

/**
 * Presenter pro vykreslování článků.
 * @package App\CoreModule\Presenters
 */
class BlogGaleriePresenter extends BasePresenter
{
    /** @var int */
	private $bloggal_id;

    /** @var string */
	private $url;

    /** @var BlogGalerieManager Model pro správu s článků. */
    public BlogGalerieManager $bloggalerieManager;

    /** @var CategoryManager */
    //public CategoryManager $categoryManager;

    /** @var ImageAltManager */
    public ImageAltManager $imagealtManager;

    /** @var ImageHideManager */
    public ImageHideManager $imagehideManager;

    /** @var ProductImageManager Instance třídy modelu pro práci s obrázky produktů. */
    private ProductImageManager $productimageManager;

    /** @var BlogGalerieFormFactory Továrnička pro formulář produktu. */
    private BlogGalerieFormFactory $blogGalerieFormFactory;

    /**
     * Konstruktor s nastavením URL výchozího článku a injektovaným modelem pro správu článků.
     * @param BlogGalerieManager $bloggalerieManager    automaticky injektovaný model pro správu článků
     * @param CategoryManager $categoryManager
     * @param ImageAltManager $imagealtManager
     * @param ImageHideManager $imagehideManager
     * @param BlogGalerieFormFactory $bloggalerieFormFactory automaticky injektovaná třída pro vytvoření formuláře pro produkty
     */
    public function __construct(BlogGalerieManager $bloggalerieManager, CategoryManager $categoryManager, ImageAltManager $imagealtManager, ImageHideManager $imagehideManager, BlogGalerieFormFactory $blogGalerieFormFactory, ProductImageManager $productimageManager)
    {
        parent::__construct();
        $this->bloggalerieManager = $bloggalerieManager;
        $this->categoryManager = $categoryManager;
        $this->imagealtManager = $imagealtManager;
        $this->imagehideManager = $imagehideManager;
        $this->blogGalerieFormFactory = $blogGalerieFormFactory;
        $this->productimageManager = $productimageManager;
    }

	public function actionDetail(int $id) {
        // Pokud není zadaná URL, vezme se URL výchozího článku.
		$this->bloggal_id = $id;
	}
    public function actionDetailUrl(string $url) {
        // Pokud není zadaná URL, vezme se URL výchozího článku.
		$this->url = $url;
	}

    /**
     * Načte a předá článek do šablony podle jeho URL.
     * @param string|null $url URL článku
     * @throws BadRequestException Jestliže článek s danou URL nebyl nalezen.
     */

    public function renderDefault(string $url = null)
    {
        $parameters = $this->getParameters();
        $bloggaleries = $this->bloggalerieManager->getBlogGaleriesKategorie($parameters);
        
        $this->template->bloggaleries = $bloggaleries;
    }

	public function renderDetail(int $id) {

        $this->renderPageing($id);
        
		try {
			$this->template->bloggalerie = $this->bloggalerieManager->getBlogGalerieFromId($id);
		} catch (\Exception $e) {
			$this->error('Článek nebyl nalezen');
		}
	}

	public function renderGrid(int $id) {

		try {
			$this->template->bloggalerie = $this->bloggalerieManager->getBlogGalerieFromId($id);
		} catch (\Exception $e) {
			$this->error('Článek nebyl nalezen');
		}
	}

    /** Načte a předá seznam článků do šablony. */
    public function renderList()
    { 
        $this->template->bloggaleries = $this->bloggalerieManager->getAllBlogGaleries();
    }
    
    /**
     * Odstraní článek.
     * @param string|null $url URL článku
     * @throws AbortException
     */
    public function actionRemove(string $url = null)
    {
        $this->bloggalerieManager->removeBlogGalerii($url);
        $this->flashMessage('Článek byl úspěšně odstraněn.');
        $this->redirect('BlogGalerie:list');
    }

    /**
     * Vytváří formulář pro správu produktu
     * @return Form formulář pro správu produktu
     */
    protected function createComponentBlogGalerieForm()
    {
        $form = $this->blogGalerieFormFactory->create(function ($form, $values) {
            $this->flashMessage('Produkt byl úspěšně uložen.');//, self::MSG_SUCCESS
            $this->redirect(':Core:BlogGalerie:');//, $values->bloggal_id , $values->url detail ... tady bude teď bez detailu protože nově vytvořená bloggal ještě nemá id a hází chybu!
        }, function () {
            $this->flashMessage('Galerie s touto URL adresou již existuje.');//, self::MSG_ERROR
        });
        return $form;
    }

    /**
     * Vytváří a vrací komponentu pro zobrazení a manipulaci s již nahranými obrázky u produktu.
     * @return GalerieImagesControl komponenta pro zobrazení a manipulaci s již nahranými obrázky u produktu
     */
    protected function createComponentGalerieImages()
    {
        return new GalerieImagesControl($this->bloggalerieManager, $this->productimageManager, $this->imagealtManager, $this->imagehideManager);
    }

    /**
     * Správa produktu.
     * @param string $url URL adresa produktu, který editujeme; pokud není zadána, přidá se produktu jako nový
     */
    public function actionManage($url)
    {
        if ($url && ($product = $this->bloggalerieManager->getBloggalerii($url))) {
            $bloggalerie = $product->toArray(); // IRow je read-only, pro manipulaci s ním ho musíme převést na pole.
            $this->template->bloggalerie = $bloggalerie;
            $this['blogGalerieForm']->setDefaults($bloggalerie); // 'bloggalerie'
            $this['galerieImages']->setBlogGalerie($product); // Nastaví již existující produkt do komponenty.
        }else $this->template->bloggalerie['url'] = '';
    }

    /**
     * Správa produktu.
     * @param string $url URL adresa produktu, který editujeme; pokud není zadána, přidá se produktu jako nový
     */
    public function renderManage($url)
    {
        $this->template->title = $url ? 'Editace produktu' : 'Nový produkt';// Definuje komponentu, která se vykreslí v rámci formuláře.
        $this->template->galerieImagesWidget = ArrayHash::from(['name' => 'galerieImages', 'after' => 'popisek']);
    }

    /**
     * Fragment fce pro listování....
     * @param string $url URL adresa produktu, který editujeme; pokud není zadána, přidá se produktu jako nový
     */
    public function renderPageing($id)
    {
        $bloggaleriesId = $this->bloggalerieManager->getBlogGaleriesId();
        $bloggaleriesUrl = $this->bloggalerieManager->getBlogGaleriesUrl();
        $ids = array_flip($bloggaleriesId);
        $poradi = $ids[$id];$prev = $poradi-1; $next = $poradi+1;
        
        if ((count($bloggaleriesId))==1) {$prevId = $nextId = $id; $prevUrl = $nextUrl = $url;dump($url);}
        else{
            if ($poradi == 0) $prev = 0;
            elseif ($poradi == (count($bloggaleriesId))-1) $next = $poradi;
            $prevId = $bloggaleriesId[$prev];
            $nextId = $bloggaleriesId[$next];
            $prevUrl = $bloggaleriesUrl[$prev];
            $nextUrl = $bloggaleriesUrl[$next];
        } 

        $this->template->prev = $prevId;
        $this->template->next = $nextId;
        $this->template->prevUrl = $prevUrl;
        $this->template->nextUrl = $nextUrl;
    }
}