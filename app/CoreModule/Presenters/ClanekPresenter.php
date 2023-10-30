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

declare(strict_types=1);

namespace App\CoreModule\Presenters;

use App\CoreModule\Model\ClanekManager;
use App\CoreModule\Model\BlogGalerieManager;
use App\CoreModule\Model\CategoryManager;
use App\CoreModule\Model\RedactionManager;
use App\CoreModule\Model\ServisManager;
use App\CoreModule\Model\CommentManager;
use App\Presenters\BasePresenter;
use App\Components\Comments;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime; // - ověř, kde se pracuje s date - naposledy v plánování vydání článku!
use Nette\Http\FileUpload;

/**
 * Presenter pro vykreslování článků.
 * @package App\CoreModule\Presenters
 */
class ClanekPresenter extends BasePresenter
{
    /** @var int */
	private $article_id;

    /** @var string */
	//private $soubory; SLAB - je třeba zakládat proměnnou?!!
    
    /** @var string URL výchozího článku. */
    private string $defaultClanekUrl;

    /** @var ClanekManager Model pro správu s článků. */
    public ClanekManager $clanekManager;

    /** @var BlogGalerieManager Model pro správu s článků. */
    public BlogGalerieManager $bloggalerieManager;

    /** @var RedactionManager Model pro správu s článků. */
    public RedactionManager $redactionManager;

    /** @var ServisManager Model pro správu s článků. */
    public ServisManager $servisManager;
    
	/** @var CommentManager */
	private $commentManager;

    /**
     * Konstruktor s nastavením URL výchozího článku a injektovaným modelem pro správu článků.
     * @param string         $defaultClanekUrl URL výchozího článku
     * @param ClanekManager $clanekManager    automaticky injektovaný model pro správu článků
     * @param CategoryManager $categoryManager
     * @param CategoryManager $categoryManager
	 * @param CommentManager $commentManager
     */
    public function __construct(string $defaultClanekUrl, ClanekManager $clanekManager, BlogGalerieManager $bloggalerieManager, RedactionManager $redactionManager, ServisManager $servisManager, CommentManager $commentManager)
    {
        parent::__construct();
        $this->defaultClanekUrl = $defaultClanekUrl;
        $this->clanekManager = $clanekManager;
        $this->bloggalerieManager = $bloggalerieManager;
        $this->redactionManager = $redactionManager;
        $this->servisManager = $servisManager;
		$this->commentManager = $commentManager;
    }
	
	public function nactiAutory()// : void //parametr autor/$this->nactiAutory($autor)/ ...vyluč z pole autorů - nette nenabízí uloženého autora "navíc" ale pouze ověří zda v poli možných autorů je a jen ho zobrazí
	{
		$allautors = array('' => 'autor neuveden', 'Petra Kulagová' => 'Petra Kulagová','Jan Brenk' => 'Jan Brenk','Mirek Navrátil' => 'Mirek Navrátil', 'Ondřej Kropáč' => 'Ondřej Kropáč', 'admin' => 'administrátor');
        $username = $this->user->identity->username;
        if (!array_search($username, array_keys($allautors))){// to je podmínka pro případ, že přihlášený není vůbec na seznamu autorů
            $allautors[$username] = 'jako (nového) autora ulož přihlášeného uživatele';
            $allautors = array_reverse($allautors);
        }
        return $allautors;
	}

	public function actionDetail(int $id) {
		$this->article_id = $id;
	}

    /** Načte a předá výpis článků do šablony. 
     * Zobrazuje články podle kategoríí, autora
    */
    public function renderList(string $url = null)
    {
        $categories = $this->categoryManager->getAllCategoryName();
        if (in_array($url, array_keys($categories))||(!$url))
        $this->categoryFilter($url);
        else {
            if ($url && ($autor = array_keys($autors = $this->nactiAutory()))) { 
            } elseif ($url) {
                $this->flashMessage('Vybraný autor nenalezen ve výběru, kontaktuj admina.');
                // odeslat chybovou hlášku do DB servis notes!
                $this->redirect('Clanky');
            }
            $this->template->categoryName = isset($url) ? $url : 'Články';
            if (in_array($url, $autor)) {
            $this->template->clanky = $this->clanekManager->getClankyAutora($url);
            }
        }
    }

    public function categoryFilter(string $url = null,$filter = null)
    {
        $parameters = $this->getParameters();
        if (!isset($parameters['filter'])) $parameters['filter']='';
        if ($url && ($category = $this->categoryManager->getCategory($url))) {
            $parameters['category_url'] = $category->url;
        } elseif ($url) {
            $this->flashMessage('Zvolená kategorie neexistuje.');
            $this->redirect('Clanek:');
        }
        $parameters['onlyapprove'] = (!($this->getUser()->isLoggedIn()));
        $products = $this->clanekManager->getClankyKategorie($parameters);
        $this->template->categoryName = isset($category) ? $category->name : 'Články';
        $this->template->clanky = $products;
    }
    
    /** Načte a předá seznam článků do šablony. 
     * Zobrazuje články podle kategoríí, schválení/all-pro admina/, vzestupné a sestupné řazení
    */
    public function renderDefault($url,$filter = null)
    {
        $this->createMonthArray();// přesunuto do basepresenteru!

        $this->template->actual = $this->template->all = '';
        if ($filter == 1) {$this-> template->all = true; $approve=true;} //parametr filter přenáší jak řazení, tak výpis neschválených all je parametr nastavení řazení výpisu
        else {$this->template->actual = $filter; $this->template->all = false; $approve=false;}//' DESC'

        //pouze schválené oblíbené články pro nepřihlášené 
        $this->template->favourites = $this->clanekManager->getClankyViews(!($this->getUser()->isLoggedIn())); //filtr schválené!

        $this->template->url = $url;// vloženo pro potřeby filtování podle asc/desc/předání do handleAscendingClanky z šablony ale smysl je furt filtr podle kategorií!!!/
        if (!$url) $this->template->clanky = $this->clanekManager->getAllClanky($filter,$approve);
        else $this->categoryFilter($url);

        // schválení naplánovaných přehoď do basepresu
        $nonAppClanky = $this->clanekManager->getNonApproveClanky();
        foreach ($nonAppClanky as $clanek){
            if ((DateTime::from($clanek->publikace)->__toString()) <= date('Y-m-d H:i:s')){
                $this->clanekManager->approveClanek($clanek->url, true, $clanek->publikace);
            }
        }
    }

    /** Načte a předá seznam schválených článků do šablony. */
    public function renderLast($filterApprove = false)
    { 
        $this->template->filter = $filterApprove;
        $this->template->clanky = $this->clanekManager->getApproveClanky($filterApprove);
        $this->template->notApprovedClanky = $this->clanekManager->getNonApproveClanky();
       
        
    $clankyArchyve = $this->clanekManager->getArchyveClanky();
    
    foreach ($clankyArchyve as $clanekArchyve){
        $clankyArchyveArray[$clanekArchyve->url][] = $clanekArchyve;
    }
        $this->template->clankyArchyve = $clankyArchyve;
        $this->template->clankyArchyveArray = $clankyArchyveArray;
        $this->template->redactions = $this->redactionManager->getAllRedactiontasks();
    }

    /** handlle pro tlačítko zobrazující články vzestupně. */
    public function handleAscendingClanky($url,$filter) {
        $filter ? $filter = '' : $filter = ' DESC'; //$filter = $filter ? '' : ' DESC';
        $this->redirect('Clanek:default', $url, $filter); // předávám dva parametry první je nula!
    }

    /** handlle pro tlačítko zobrazující články včetně neschválených. */
    public function handleAllClanky($url,$filter) {
        $filter ? $filter = '' : $filter = 1;
        $this->redirect('Clanek:default', $url, $filter); // předávám dva parametry první je nula!
    }

	public function renderDetail(int $id) {

        if (!($this->getUser()->isLoggedIn())) $onlyapprove = true;
        else $onlyapprove = false;
        $this->renderPageing($id, $onlyapprove);
        
        // nastavení obrázku uloženého v DB tabulce servis jako homepage obrázek
        $this->servisManager->homepageSetting();
        $this->template->homepagePic ='images/homepage/homepagePicture.jpg';

        // pro načtení tags
        $this->template->cid = $id;
        
		try {
			$clanek = $this->template->clanek = $this->clanekManager->getClanekFromId($id);
            
            $this->clanekManager->countViewClanek($clanek['url']);//přičtení shlédnutí článku
            $this->template->viewsCount = $clanek['viewsCount'];
            $this->template->imagesCount = $clanek['imagesCount'];

            if ($clanek['galerie']) {
                // Načtení galerie ke články podle proměnné 'galerie'
                $bloggalerie = $this->bloggalerieManager->getBloggalerieFromUrl($clanek['galerie']);
                $this->template->urlgal = $bloggalerie['url'];
                $this->template->imgcountgal = $bloggalerie['imagesCount'];
            }else{
                $bloggalerie = 0;
                $this->template->urlgal = '';
            }
                $this->template->bloggalerie = $bloggalerie;

		} catch (\Exception $e) {
			$this->error('Článek nebyl nalezen');
		}
	}

    /** handlle pro tlačítko zobrazující schválené články. */
    public function handleApprovedClanky() {
        $filterApprove = TRUE;
        $this->flashMessage('Zobraz jen schválené.');
        $this->redirect('Clanek:last', $filterApprove);
    }
    
    public function handleApprove(string $url = null) {
        if ($publish= $this->clanekManager->getPublish($url)) {
            $pub = false;
            $this->flashMessage('Publikace článku zrušena.');
        } else {
            $pub = true;
            $this->flashMessage('Článek byl úspěšně publikován.');
        }
        $publikace = date("Y.m.d");
        $this->clanekManager->approveClanek($url, $pub, $publikace);
        $this->redirect('Clanek:default');
    }

    public function handleVersion(string $url = null, $adminnote) {
        $novaVerze = true;
        if (!($values = $this->clanekManager->getClanek($url)->toArray()))
                $this->flashMessage('Článek nebyl nalezen.'); // Výpis chybové hlášky.

    //pokud má ukládat i malou verzi se save archýv tak teď! jinak až po ukončení editace// to by ale muselo být předáno v poli values before edit!    //ještě save time by tady sedl aktuální!
    $this->clanekManager->saveArchyveClanek($values);
    $this->flashMessage('Poslední verze uložena do archyvu. Editujete novou verzi článku.');
    $this->redirect('Clanek:editor', $values['url'], 0, $novaVerze);
    }

    /**
     * Odstraní obr. článeku.
     * @param string|null $url URL článku
     * @throws AbortException
     */
    public function handleRemoveImage(int $id = null, string $url = null, $i, $imagesCount)
    {
        $this->clanekManager->removeImage($id, $url, $i, $imagesCount);
        $this->flashMessage('Obrázek číslo ' .$i. ' byl úspěšně odstraněn.');
        $this->redirect('Clanek:editor', $url);
    }

    /**
     * Nastaví obr. článku jako úvodní.
     * @param string|null $url URL článku
     * @throws AbortException
     */
    public function handleChooseIndexImage($id, string $file = null)
    {
        //dump($file); pčeveď do modelu!
        $indexPath = ('images/image_' . $id .'.jpg');
        $rename='rename.jpg';
        rename($indexPath, $rename);
        rename($file, $indexPath);
        rename($rename, $file);
        //ještě je možnost obrázky neprohazovat, ale index dát na konec verzí a vybranej dát jako úvodní...
        $this->flashMessage('Obrázek číslo ' .$file. ' bude na začátku článku.');
        $this->redirect('Clanek:default');
    }
    
    /**
     * Odstraní článek.
     * @param string|null $url URL článku
     * @throws AbortException
     */
    public function actionRemove(string $url = null)
    {
        $this->clanekManager->removeClanek($url);
        $this->flashMessage('Článek byl úspěšně odstraněn.');
        $this->redirect('Clanek:list');
    }

    /**
     * Vykresluje formulář pro editaci článku podle zadané URL.
     * Pokud URL není zadána, nebo článek s danou URL neexistuje, vytvoří se nový.
     * @param string|null $url URL adresa článku
     */
    public function actionEditor(string $url = null, $adminnote = null, $novaVerze = null)
    {
        ($this->getUser()->isLoggedIn());
        $this->template->username = $this->user->identity->username; 

        if ($url) {
            if (!($clanek = $this->clanekManager->getClanek($url))){
                $this->flashMessage('Článek nebyl nalezen.'); // Výpis chybové hlášky.
                $this->redirect('Clanek:');
            }else{

                $categories = $this->categoryManager->getAllCategoryName();
                $values['category'] = (array_search($clanek->category, $categories)); // pokud najde v poli jmen kategorií její název a ne url, do values/pracovní prměnná/ uloží url kategorie
                $values['url'] = $url;
                    if (($values['category'])) {
                        $this->clanekManager->saveClanekCategory($values);
                        $clanek = $this->clanekManager->getClanek($url);
                    }else
                        $values['category'] = $clanek->category;
                
                if (empty($clanek['galerie'])) {
                    $values['galerie']='0';// pod klíčem 0 se nachází ve výsledném poli, ze kterého se ve formuláří galerie vybírají hodnota bez galerie 
                    $this->clanekManager->saveClanekCategory($values);
                    $clanek = $this->clanekManager->getClanek($url);
                }

                if (empty($clanek['approve'])) {
                    $values['approve']='0';
                    $this->clanekManager->saveClanekCategory($values);
                    $clanek = $this->clanekManager->getClanek($url);
                }
                
                //pro nulování autor...
                if (($clanek->autor == 0)||($clanek->autor == null)) {
                    $values['autor']='';$this->clanekManager->saveClanekAutor($values);
                    $clanek = $this->clanekManager->getClanek($url);
                }

                $indexImagePath = ('images/news/image_' .  $clanek->clanky_id .'*.jpg');
                $this->template->indexImage = $indexImage = glob($indexImagePath);

                //pro potřeby náhledu thumb obr v editoru
                $this->template->id = $clanek->clanky_id;
                $this->template->tags = $clanek->tags;
                $this->template->notes = $clanek->notes; // pro výpis pod formulář
                $this->template->autor = $clanek->autor;
                
                $this->renderPageing($clanek->clanky_id, null);

                $poznamky['notes']='';//pro nastavení uvnitř formu
                $poznamky['novaVerze']=$novaVerze;
                
                $this['editorForm']->setDefaults($clanek); // Předání hodnot článku do editačního formuláře.
                $this['editorForm']->setDefaults($poznamky);
                
            $this->template->imagesCount = $clanek['imagesCount'];
            $this->template->url = $clanek['url'];
            }
        }else{
                //pro potřeby náhledu thumb obr v editoru
            $this->template->id = '';
            $this->template->notes = '';
            $this->template->autor = '';
        }
    }

    /**
     * Vytváří a vrací formulář pro editaci článků.
     * @return Form formulář pro editaci článků
     */
    protected function createComponentEditorForm()
    {
        $parameters = $this->getParameters();
        if (!isset($parameters['adminnote'])) $parameters['adminnote'] = false;
        if (!isset($parameters['novaVerze'])) $parameters['novaVerze'] = false;
        // Vytvoření formuláře a definice jeho polí.
        $form = new Form;
        $form->addHidden('clanky_id');
        $form->addHidden('imagesCount');
        $form->addText('titulek', 'Titulek')->setRequired();
        $form->addText('url', 'URL')->setRequired();
        $form->addUpload('picture', 'Obrázek(úvod):')
                ->setRequired(false)
                ->addCondition(Form::FILLED)
                ->addRule(Form::IMAGE);
        $form->addText('popisek', 'Popisek')->setRequired();
        $form->addText('klicova_slova', 'Klíčová slova');
        $categories = $this->categoryManager->getAllCategoryName();
        $form->addSelect('category', 'Kategorie:', $categories)
                ->setPrompt('Zvolte kategorii');
        //načtení galerií pro článek a přidání prázdné na první místo pole pro select
        $bloggaleriesUrl = $this->bloggalerieManager->getBloggaleriesUrlSet();
        array_unshift($bloggaleriesUrl, 'prazdna');
        $form->addSelect('galerie', 'Galerie:', $bloggaleriesUrl)
                ->setPrompt('Zvolte přiloženou galerii');
        $form->addMultiUpload('images', 'Obrázky')->setHtmlAttribute('accept', 'image/*')
                ->setRequired(false)
                ->addRule(Form::IMAGE, 'Formát jednoho nebo více obrázků není podporován.');

        $form->addTextArea('obsah', 'Obsah');

        $autors = $this->nactiAutory();
        $form->addSelect('autor', 'Autor článku:', $autors);

        //všechny přenášecí parametry - určují které promené edituju/ které části fomu zobrazím - $form->addHidden('adminnote');$form->addHidden('dateofsave');$form->addHidden('novaVerze');
        $form->addHidden('novaVerze');
        $form->addHidden('verze');
        if (($parameters['adminnote'] == TRUE)) {
            $form->addText('notes', 'Zapiš admin poznámku')->setRequired();
            $form->addCheckbox("dateofsave", "Uložit s datem")->setDefaultValue(0);
            $form->addHidden('adminnote');
            $form->addSubmit('save', 'Uložit článek s poznámkou');
            }
        elseif($parameters['novaVerze'] == TRUE){
            $form->addHidden('adminnote');$form->addHidden('dateofsave');
            $form->addHidden('notes');
            $form->addSubmit('save', 'Uložit novou verzi');//verzi s poznámkou
        }
        else{ 
            $form->addCheckbox("adminnote", "Doplň poznámku");
            $form->addHidden('notes');$form->addHidden('dateofsave');

        $form->addSubmit('save', 'Uložit článek');
        }

        // Funkce se vykonaná při úspěšném odeslání formuláře a zpracuje zadané hodnoty.
        $form->onSuccess[] = function (Form $form, ArrayHash $values) {

            if ((file_exists($oldPath = 'images/image_' . $values['clanky_id'] . '.jpg'))&(($values['picture'])->hasFile())){
                    $filePath = ('images/news/image_' . $values['clanky_id'] .'*.jpg');
                    $soubory = glob($filePath);
                    $soubory = array_reverse($soubory);

                if (file_exists($file = 'images/news/image_' . $values['clanky_id'] . '_0.jpg')){
                    
                        //soubory od nejnovějšího rozdelí podle tečky a první část z pole $fileindex ...
                        $filename = explode('.', ($soubory[0]));
                        $fileindex = explode('_', ($filename[0]));
                        $index = (end($fileindex));

                }else $index = -1;
                    $newPath = 'images/news/image_'. $values['clanky_id'] . '_' . ($index+1) . '.jpg';
                    if (file_exists($oldPath)) rename($oldPath, $newPath);
                }
            try {
                if ($values['adminnote']){
                        $adminnote = TRUE;
                        $this->redirect('Clanek:editor', $values->url, $adminnote, 0);
                    }
                $values->offsetUnset('adminnote');
                
                $id = (int)$values->clanky_id;
                if (($clanek = $this->clanekManager->getClanek($values->url)))
                
                if (isset($clanek['notes'])) {
                    $polenotes = explode(',', $clanek['notes']);
                    if ($values['notes']) $polenotes[] = ($values['notes']); // bez podmínky se při každém uložení přidá čárka!
                    $stringnotes = implode(',', $polenotes);
                    }else{
                    $polenotes[0] = $stringnotes = ($values['notes']); 
                    }
                if ($values['dateofsave'] == true) {
                    $polenotes[] = 'datum ' . date("Y.d.m");
                    $stringnotes = implode(',', $polenotes);
                }
                $values->offsetUnset('dateofsave');
                
                if (isset($stringnotes)) $values->notes = $stringnotes;

                if ($values['novaVerze'] == true) {

                    $novaVerze=($values['verze']+1);
                    $values['verze'] = $values['verze']+1;
                    $this->flashMessage('Článek byl úspěšně uložen ve verzi...' . $novaVerze);
                    
                }
                $values->offsetUnset('novaVerze');
                
                    // Převede pole obecných souborů na pole obrázků.//
                    $images = array_map(function (FileUpload $image) {
                        return $image->toImage();
                    }, $values->images);
                    $values->offsetUnset('images');

                $actualClanekImagesCount = ($values['imagesCount']);

                if (!$values->clanky_id) $values->autor = ($this->user->identity->username);
                $this->clanekManager->saveClanek($values);
                
                $galerieUrl = $values->url;
                $this->clanekManager->saveClanekgalerieImages($galerieUrl, $images, $actualClanekImagesCount);
                $this->flashMessage('Článek byl úspěšně uložen.');
                $this->redirect('Clanek:');
            } catch (UniqueConstraintViolationException $e) {
                $this->flashMessage('Článek s touto URL adresou již existuje.');
            }
        };
        return $form;
    }


	public function createComponentComments(): Comments {
		return new Comments($this->commentManager, $this->article_id);
	}

    /**
     * Funkce pro listování....
     * @param string $url/id URL adresa produktu, který editujeme; pokud není zadána, přidá se produktu jako nový
     */
    public function renderPageing($id, $approve)
    {
        $clankyId = $this->clanekManager->getclankyId($approve);
        $clankyUrl = $this->clanekManager->getclankyUrl($approve);
        $clankyAllUrl = $this->clanekManager->getclankyAllUrl();
        
        // pro úvodní neschválený článek
        if (($id == 1)&($approve)) $clankyId[] = 1 ;
        $ids = array_flip($clankyId);
        if ((in_array($id, (array_keys($ids))))) {
        $poradi = $ids[$id]; $prev = $poradi-1; $next = $poradi+1;
        }else{
            $this->flashMessage('Pokoušíš se otevřít nedostupný nebo neschválený článek.');
            $this->redirect('Clanek:');
        }
        
    if ((count($clankyId))==1) {$prevId = $nextId = $id; $prevUrl = $nextUrl = $url;}
        else{
            //pro první a poslední položku nastaven index /prev, next/ napevno
            if ($poradi == 0) $prev = 0;
            elseif ($poradi == (count($clankyId))-1) $next = $poradi;
            // pro úvodní neschválený článek
            
            $prevId = $clankyId[$prev];
            $prevUrl = $clankyUrl[$prev];
            $prevAUrl = $clankyAllUrl[$prev];
            if (($id == 1)&($approve==1)) {
                $prevId = $id;
                $prevUrl = 'uvod';
                $next = 0;
            }
            
            $nextId = $clankyId[$next];
            $nextUrl = $clankyUrl[$next];
            $nextAUrl = $clankyAllUrl[$next];
        } 

        $this->template->prev = $prevId;
        $this->template->next = $nextId;
        $this->template->prevUrl = $prevUrl;
        $this->template->nextUrl = $nextUrl;
        $this->template->prevAUrl = $prevAUrl;
        $this->template->nextAUrl = $nextAUrl;
    }

    public function handleInwork(int $id = null) {
        $inwork= $this->redactionManager->getStav($id);
        if (($inwork=='')||($inwork=='stop')) {
            $stav = 'iw';
            $this->flashMessage('Rozpracováno.');
        } elseif ($inwork=='iw'){
            $stav = 'stop';
            $this->flashMessage('Pozastaveno.');
        }
        $this->redactionManager->inworkRedaction($id, $stav);
        $this->redirect('Clanek:last');
    }
    
    public function handleFinal(int $id = null) {
            $stav = 'done';
            $this->flashMessage('Dokončeno.');
        $this->redactionManager->inworkRedaction($id, $stav);
        $this->redirect('Clanek:last');
    }
}