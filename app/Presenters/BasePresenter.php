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

namespace App\Presenters;


use App\Forms\FormFactory;
use App\CoreModule\Model\CategoryManager;
use App\CoreModule\Model\ClanekManager;
use App\CoreModule\Model\OnlineManager;
use App\CoreModule\Model\BlogGalerieManager;
//use App\Model\DatabaseManager;
use Nette\Application\AbortException;
use Nette\Application\UI\Presenter;
use Nette\Utils\DateTime;
//use Nittro\Bridges\NittroUI\Presenter;

/**
 * Základní presenter pro všechny ostatní presentery aplikace.
 * @package App\Presenters
 */
abstract class BasePresenter extends Presenter
{
    /** @var FormFactory Továrna na formuláře. */
    protected FormFactory $formFactory;

    /** Cesta ke globální šabloně formulářů. */
    const FORM_PATH = __DIR__ . '/../templates/forms/form.latte';

        /** @var CategoryManager */
        protected $categoryManager;
    
        /** @var ClanekManager Model pro správu s článků. */
        public ClanekManager $clanekManager;
        public BlogGalerieManager $bloggalerieManager;
        
        /** @var OnlineManager Model pro správu s článků. */
        public OnlineManager $onlineManager;

        /**
         * @param CategoryManager $categoryManager
         */
        public function injectManagerDependencies(
                CategoryManager $categoryManager, ClanekManager $clanekManager, OnlineManager $onlineManager, BlogGalerieManager $bloggalerieManager) {
            $this->categoryManager = $categoryManager;
            $this->clanekManager = $clanekManager;
            $this->bloggalerieManager = $bloggalerieManager;
            $this->onlineManager = $onlineManager;
        }

    /**
     * Získává továrnu na formuláře pomocí DI.
     * @param FormFactory $formFactory automaticky injektovaná továrna na formuláře
     */
    public final function injectFormFactory(FormFactory $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * Na začátku každé akce u všech presenterů zkontroluje uživatelská oprávnění k této akci.
     * @throws AbortException Jestliže uživatel nemá oprávnění k dané akci a bude přesměrován.
     */
    protected function startup()
    {
        parent::startup();
        if (!$this->getUser()->isAllowed($this->getName(), $this->getAction())) {
            $this->flashMessage('Nejsi přihlášený nebo nemáš dostatečná oprávnění.');
            $this->redirect(':Core:Administration:login');
        }
    }

    protected function beforeRender() {
        parent::beforeRender();
        $this->template->categories = $categories = $this->categoryManager->getCategories();
        
        //nčtení kategoriií do záhlaví stránky
        $i = 0;
        foreach ($categories as $category){
            $clankykategorie[$i] = ($this->clanekManager->vratPocetClankuKategorie($category['url'], ($this->getUser()->isLoggedIn()))); 
            $i++;
            }
            $this->template->pocetclankukategorie = $clankykategorie;

        $this->createMonthArray();
            
        $this->template->formPath = __DIR__ . '/../templates/forms/form.latte'; // Předá cestu ke globální šabloně formulářů do šablony.

        $this->template->formPath = self::FORM_PATH; // Předá cestu ke globální šabloně formulářů do šablony.
        /*if ($this->user->isLoggedIn()) {
            $this->template->username = $this->user->identity->username;
            //$this->template->role = $this->user->identity->role;
        }*/
        $this->template->lastonline = $this->onlineManager->getOnlinePosledni();

        $this->template->posledni = $this->clanekManager->getClankyPosledni(!($this->getUser()->isLoggedIn()))->toArray();

        $this->template->lastbloggaleries = $this->bloggalerieManager->getLastBlogGalerii();
    }

    public function createMonthArray(){
        //načtení článku podle vydání v jednotlivých měsících...
        $podleMesicu = $this->clanekManager->getClankyPub(!($this->getUser()->isLoggedIn())); // PUVODNÍ DOTAZ SELECT `publikace`, `url`

        foreach ($podleMesicu as $jeden){
            $polePodleMesicu[(idate('m', strtotime(DateTime::from($jeden['publikace'])->__toString())))][] = array($jeden['url'], $jeden['clanky_id']);
            //$polePodleMesicu[(idate('m', strtotime(DateTime::from($jeden['publikace'])->__toString())))][][1] = $jeden['clanky_id'];// snad by id šlo předat líp! - *
        }
        $this->template->poleMesicu = $polePodleMesicu;
    }
}