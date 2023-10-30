<?php

declare(strict_types=1);

namespace App\CoreModule\Presenters;

use App\CoreModule\Model\GalleryManager;
use App\CoreModule\components\GalleryImagesControl;
use App\CoreModule\components\ReviewControl;
use App\CoreModule\components\RatingControl;
use App\CoreModule\Model\ReviewManager;
use App\CoreModule\Model\ServisManager;
use App\Forms\ReviewFormFactory;
use App\Forms\GalerieFormFactory;
use App\Presenters\BasePresenter;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\ArrayHash;
use App\Model\UserManager;

/**
 * Presenter pro vykreslování článků.
 * @package App\CoreModule\Presenters
 */
class GalleryPresenter extends BasePresenter
{
    /** @var int */
	private $gallery_id;

    /** @var GalleryManager Model pro správu s článků. */
    public GalleryManager $galleryManager;

    /** @var ReviewManager Instance třídy modelu pro práci s recenzemi. */
    private ReviewManager $reviewManager;

    /** @var ServisManager Instance třídy modelu pro práci s recenzemi. */
    private ServisManager $servisManager;

    /** @var ReviewFormFactory Továrnička pro review formulář. */
    private ReviewFormFactory $reviewFormFactory;

    /** @var GalerieFormFactory Továrnička pro formulář produktu. */
    private GalerieFormFactory $galerieFormFactory;

// ZAVEDENO KVŮLI USER NAME PRO REVIEW PŘI NEPOUŽITÍ ZRUŠ!!! 

    /** @var UserManager Instance třídy modelu pro práci s recenzemi. */
    private UserManager $userManager;

    /**
     * Konstruktor s nastavením URL výchozího článku a injektovaným modelem pro správu článků.
     * @param GalleryManager $galleryManager    automaticky injektovaný model pro správu článků
     * @param CategoryManager $categoryManager
     * @param GalerieFormFactory $galerieFormFactory automaticky injektovaná třída pro vytvoření formuláře pro produkty
     * @param ReviewManager        $reviewManager       automaticky injektovaná třída modelu pro práci s recenzemi
     * @param ServisManager        $servisManager       automaticky injektovaná třída modelu pro práci s nastavením
     * @param ReviewFormFactory    $reviewFormFactory   automaticky injektovaná třída pro vytvoření formuláře pro recenze
     */
    public function __construct(GalleryManager $galleryManager, GalerieFormFactory $galerieFormFactory, ReviewManager $reviewManager, ServisManager $servisManager, ReviewFormFactory $reviewFormFactory, UserManager $userManager)
    {
        parent::__construct();
        $this->galleryManager = $galleryManager;
        $this->galerieFormFactory = $galerieFormFactory;
        $this->reviewManager = $reviewManager;
        $this->servisManager = $servisManager;
        $this->reviewFormFactory = $reviewFormFactory;
        $this->userManager = $userManager;
    }

	public function actionDetail(int $id) {
        // Pokud není zadaná URL, vezme se URL výchozího článku.NE!
		$this->gallery_id = $id;
	}

    /**
     * Načte a předá článek do šablony podle jeho URL.
     * @param string|null $url URL článku
     * @throws BadRequestException Jestliže článek s danou URL nebyl nalezen.
     */

    public function renderDefault(int $id = null)
    {        
        $galleries = $this->galleryManager->getAllGalleries();   
        $this->template->galleries = $galleries;
    }

	public function renderDetail(int $id) {
		try {
			$this->template->gallery = $gallery = $this->galleryManager->getGalleryFromId($id);
		} catch (\Exception $e) {
			$this->error('Článek nebyl nalezen');
		}

        // Nastavení výchozí hodnoty formuláře pro hodnocení produktu. //pozn.: getPrimary() vrátí primární klíč
        $this['reviewForm']->setDefaults(['gallery_id' => $gallery->getPrimary()]);// id hodnoceného produktu 

        // Předání parametrů do šablony.
        $this->template->reviews = $this->reviewManager->getReviews($gallery->gallery_id);

        // Definuje komponentu, která se vykreslí v rámci formuláře pro psaní recenzí.
        $this->template->ratingWidget = ArrayHash::from(['name' => 'rating', 'after' => 'gallery_id']);

	}

    /**
     * Vytváří a vrací komponentu formuláře pro hodnocení produktu.
     * @return Form formulář pro hodnocení produktu
     */
    protected function createComponentReviewForm()
    {
        $form = $this->reviewFormFactory->create(function($form, $values){
            $this->flashMessage('Recenze byla úspěšně přidána, děkujeme.');
            $this->redirect('this');
        }, function($form, $values){
            $this->flashMessage('Recenze mohou přidávat jen přihlášení uživatelé.');
            $this->redirect('this');
        }, function($form, $values){
            $this->flashMessage('Tento produkt jsi již hodnotil.');
        });
        return $form;
	}

    /** Načte a předá seznam článků do šablony. */
    public function renderList(string $filter = null)
    { 
        $galleries = $this->galleryManager->getAllGalleries();
        $this->template->galleries = $galleries;
        $this->template->filter = $filter;
    }
    
    /**
     * Odstraní článek.
     * @param string|null $url URL článku
     * @throws AbortException
     */
    public function actionRemove(int $id = null)
    {
        $this->galleryManager->removeGallery($id);
        $this->flashMessage('Článek byl úspěšně odstraněn.');
        $this->redirect('Gallery:list');
    }

    /**
     * Změní nastavení umístění v portfóliu.
     * @param string|null $id ID článku
     */

    public function handlePortfol(int $id = null) {
        if ($portfol= $this->galleryManager->getPortfol($id)) {
            $prf = false;
            $this->flashMessage('Publikace obrázku v portfoliu zrušena.');
        } else {
            $prf = true;
            $this->flashMessage('Obrázek byl úspěšně publikován v portfoliu.');
        }
        $this->galleryManager->portfolGallery($id, $prf);
        $this->redirect('Gallery:list');
    }

    /**
     * Změní nastavení umístění ve výberu úvodních obrázků.
     * @param string|null $id ID článku
     */

    public function handleDefault(int $id = null) {
        if ($portfol= $this->galleryManager->getDefault($id)) {
            $def = false;
            $this->flashMessage('Publikace obrázku v úvodu zrušena.');
        } else {
            $def = true;
            $this->flashMessage('Obrázek byl úspěšně publikován v úvodu.');
        }
        $this->galleryManager->defaultGallery($id, $def);
        $this->redirect('Gallery:list');
    }
 
    /**
     * Změní nastavení umístění ve výberu úvodních obrázků.
     * @param string|null $id ID článku
     */

    public function handleHomepage(string $file_name = null) {
        $homepage['name'] = 'homepage';
        $homepage['setting'] = $file_name;
        $this->servisManager->addSetting($homepage);
        $this->redirect('Gallery:list');
    } 

    /**
     * Vytváří formulář pro správu produktu
     * @return Form formulář pro správu produktu
     */
    protected function createComponentGalerieForm()
    {
        $form = $this->galerieFormFactory->create(function ($form, $values) {
            $this->flashMessage('Produkt byl úspěšně uložen.');
            $this->redirect(':Core:Gallery:');
        }, function () {
            $this->flashMessage('Produkt s touto URL adresou již existuje.');
        });
        return $form;
    }

// koncepce výběru obrázků do sekcí obchod/uvodní list
 
    /**
     * Vytváří a vrací formulář pro editaci článků.
     * @return Form formulář pro editaci článků
     */
    protected function createComponentImageuseForm()
    {
        // Vytvoření formuláře a definice jeho polí.
        $form = new Form;
 
        $form->addHidden('gallery_id'); 
        $form->addCheckboxList('choice', 'Použít:', [
            'default' => 'úvodní stránka',
            'portfol' => 'portfólio',
        ]);
        $form->addSubmit('usage', 'Ok');

        $form->onSuccess[] = function (Form $form, ArrayHash $values) {

            try {
                $choices = $values->choice;
                foreach ($choices as $choice){
                    if ($choice == 'default') $settings['startPage'] = true;
                    if ($choice == 'portfol') $settings['portfolio'] = true;
                    $this->galleryManager->saveSetting($values->gallery_id, $settings);
                }
                $this->flashMessage('Přiřazení obrázku zaznamenáno.');
                $this->redirect('this');
            } catch (UniqueConstraintViolationException $e) {
                $this->flashMessage('Článek s touto URL adresou již existuje.');
            }
        };
        return $form;
    }

    //form pro nahrávání s progress berem/ringem

    /**
     * Vytváří a vrací formulář pro editaci článků.
     * @return Form formulář pro editaci článků
     */
    protected function createComponentImageUploadForm()
    {
        // Vytvoření formuláře a definice jeho polí.
        $form = new Form;
 
        $form->addHidden('gallery_id'); // jak to tam dostat jedině komponentou?
        $form->addHidden('imgFullNameGalerie');
		$form->addUpload('picture', 'Obrázek:')
                ->setRequired(false)
                ->addCondition(Form::FILLED)
                ->addRule(Form::IMAGE);
        $form->addSubmit('usage', 'Ok');

        $form->onSuccess[] = function (Form $form, ArrayHash $values) {

            try {
    			$galerie_id = $this->galleryManager->saveGallery($values);
                $this->flashMessage('Uložení obrázku dokončeno.');
                $this->redirect('this');
            } catch (UniqueConstraintViolationException $e) {
                $this->flashMessage('Chyba - URL adresa již existuje.'); //hloupost!
            }
        };
        return $form;
    }
    /**
     * Správa produktu.
     * @param string $url URL adresa produktu, který editujeme; pokud není zadána, přidá se produktu jako nový
     */
    public function actionManage($url)
    {
        if ($url && ($product = $this->galleryManager->getGallery($url))) {
            $gallery = $product->toArray(); // IRow je read-only, pro manipulaci s ním ho musíme převést na pole.
            
            $this->template->gallery = $gallery;
            $this['galerieForm']->setDefaults($gallery);
            $this['imageuseForm']->setDefaults($gallery);
            $this['imageUploadForm']->setDefaults($gallery);


            $this['galleryImages']->setGallery($product); // Nastaví již existující produkt do komponenty.
        }
        else $this->template->gallery['imgFullNameGalerie'] = ''; //jen nastaveno pro náhledu pod/vedle editoru
    }
    
    /**
     * Správa produktu.
     * @param string $url URL adresa produktu, který editujeme; pokud není zadána, přidá se produktu jako nový
     */
    public function renderManage($url)
    {
        //$this->template->gallery = $gallery;
        $this->template->title = $url ? 'Editace produktu' : 'Nový produkt';// Definuje komponentu, která se vykreslí v rámci formuláře.
        $this->template->galerieImagesWidget = ArrayHash::from(['name' => 'galleryImages', 'after' => 'popisek']);
    }

    /**
     * Vytváří a vrací komponentu pro zobrazení a manipulaci s již nahranými obrázky u produktu.
     * @return GalleryImagesControl komponenta pro zobrazení a manipulaci s již nahranými obrázky u produktu
     */
    protected function createComponentGalleryImages()
    {
        return new GalleryImagesControl($this->galleryManager);
    }

    /**
     * Vytváří a vrací komponentu pro vykreslení ratingu.
     * @return RatingControl komponenta pro rating
     */
    protected function createComponentRating()
    {
        return new RatingControl();
    }

    /**
     * Vytváří a vrací komponentu pro vykreslení recenze produktu.
     * @return ReviewControl komponenta pro vykreslení recenze produktu
     */
    protected function createComponentReview()
    {
        return new ReviewControl($this->userManager);
    }
      
    /** handlle pro tlačítko zobrazující články včetně neschválených. * / - PATŘÍ TO SEM???? */
    public function handleOnlyGallery($filter) {
        $filter ? $filter = 1 : $filter = '';
        $this->redirect('Gallery:list', $filter); // předávalo dva parametry; $url, určené pro kategorii je nula!
    }
}