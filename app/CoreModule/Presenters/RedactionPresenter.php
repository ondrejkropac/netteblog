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
use App\CoreModule\Model\RedactionManager;
use App\CoreModule\Model\CommentManager;
use App\Presenters\BasePresenter;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\ArrayHash;

/**
 * Presenter pro vykreslování článků.
 * @package App\CoreModule\Presenters
 */
class RedactionPresenter extends BasePresenter
{
    /** @var int */
	private $redakce_id;

    /** @var ClanekManager Model pro správu s článků. */
    public ClanekManager $clanekManager;

    /** @var RedactionManager Model pro správu s článků. */
    public RedactionManager $redactionManager;

    /**
     * Konstruktor s nastavením URL výchozího článku a injektovaným modelem pro správu článků.
     * @param string         $defaultClanekUrl URL výchozího článku
     * @param ClanekManager $clanekManager    automaticky injektovaný model pro správu článků
     * @param CategoryManager $categoryManager
	 * @param CommentManager $commentManager
     */
    public function __construct(ClanekManager $clanekManager, RedactionManager $redactionManager)
    {
        parent::__construct();
        $this->clanekManager = $clanekManager;
        $this->redactionManager = $redactionManager;
    }

    public function actionRedactionDetail(int $id) {
        $this->redakce_id = $id; //redakce_id
    }

    public function renderRedactionDetail(int $id) {
        try {
            $this->template->redakce = $this->redactionManager->getRedactionFromId($id);
        } catch (\Exception $e) {
            $this->error('Zápisek nebyl nalezen');
        }
    }

    /**
     * Odstraní zápisek.
     * @param string|null $url URL článku
     * @throws AbortException
     */
    public function actionRemoveredaction(string $redakce_id = null)
    {
        $this->redactionManager->removeRedaction($redakce_id);
        $this->flashMessage('Zápisek byl úspěšně odstraněn.');
        $this->redirect('Clanek:last');
    }

    /**
     * Vykresluje formulář pro editaci článku podle zadané REDAKCE_ID.
     * Pokud REDAKCE_ID není zadána, nebo zápisek s danou REDAKCE_ID neexistuje, vytvoří se nový.
     * @param string|null $redakce_id REDAKCE_ID adresa článku
     */
    public function actionRedactionEditor(int $redakce_id = null)
    {
        if ($redakce_id) {
            if (!($redakce = $this->redactionManager->getRedaction($redakce_id)))
                $this->flashMessage('Zápisek nebyl nalezen.'); // Výpis chybové hlášky.
            else $this['redactionEditorForm']->setDefaults($redakce); // Předání hodnot článku do editačního formuláře.

            $this->template->redakce_id = $redakce_id;
        }
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

    /**
     * Vytváří a vrací formulář pro editaci článků.
     * @return Form formulář pro editaci článků
     */
    protected function createComponentRedactionEditorForm()
    {
        // Vytvoření formuláře a definice jeho polí.
        $form = new Form;
        $form->addHidden('redakce_id'); //redakces
        $form->addHidden('resitel'); 
        $form->addText('nazev', 'Titulek')->setRequired();
        $form->addText('stav', 'Stav')->setRequired();
        $clanky = $this->clanekManager->getUnpubClankyName(false);
        $form->addSelect('clanek', 'Přiřaď článek:', $clanky)
                    ->setPrompt('Zvolte kategorii');
        $form->addTextArea('notes', 'Obsah');
        $form->addSubmit('save', 'Uložit zápis');

        // Funkce se vykonaná při úspěšném odeslání formuláře a zpracuje zadané hodnoty.
        $form->onSuccess[] = function (Form $form, ArrayHash $values) {
            //uložení přiklášeného jako autora při odeslání nového redakce!
            if (!$values['redakce_id'])
            $values['resitel'] = $this->user->identity->username;
            
            try {
                $this->redactionManager->saveRedaction($values);
                $this->flashMessage('Zápis byl úspěšně uložen.');
                $this->redirect('Clanek:last');//, $values->redakce_id
            } catch (UniqueConstraintViolationException $e) {
                $this->flashMessage('Zápis s touto URL adresou již existuje.');
            }
        };

        return $form;
    }

    // pasáž editace data publikace

    /**
     * Vykresluje formulář pro editaci článku podle zadané REDAKCE_ID.
     * Pokud REDAKCE_ID není zadána, nebo zápisek s danou REDAKCE_ID neexistuje, vytvoří se nový.
     * @param string|null $redakce_id REDAKCE_ID adresa článku
     */
    public function actionPublishEditor(string $url = null)
    {
        if ($url) {
            if (!($clanek = $this->clanekManager->getClanek($url)))
                $this->flashMessage('Zápisek nebyl nalezen.'); // Výpis chybové hlášky.
            else {
            if (!($clanek->publikace)){
                    $publikace = date('Y-m-d');
                    $clanek = $this->clanekManager->getClanek($url);
                }
                $this['publishEditorForm']->setDefaults($clanek); // Předání hodnot článku do editačního formuláře.
            }

            $this->template->clanek = $clanek;
        }
    }

    /**
     * Vytváří a vrací formulář pro editaci data vydání článků.
     * @return Form formulář pro editaci článků
     */
    protected function createComponentPublishEditorForm()
    {
        // Vytvoření formuláře a definice jeho polí.
        $form = new Form;
        $form->addHidden('clanky_id'); //redakces
        $form->addHidden('url');
        $form->addText('titulek', 'Titulek')->setRequired();
        $form->addText('publikace', 'Naplánuj!')->setRequired();//->setDefaultValue(date('Y-m-d H:i:s'));
        $form->addSubmit('save', 'Uložit článek');
        // Funkce se vykonaná při úspěšném odeslání formuláře a zpracuje zadané hodnoty.
        $form->onSuccess[] = function (Form $form, ArrayHash $values) {
            
            try {
                $values->approve = '-1';
                $this->clanekManager->saveClanek($values);
                $this->flashMessage('Zápis byl úspěšně uložen.');
                $this->redirect('Clanek:last');//, $values->redakce_id
            } catch (UniqueConstraintViolationException $e) {
                $this->flashMessage('Zápis s touto URL adresou již existuje.');
            }
        };

        return $form;
    }
}