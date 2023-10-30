<?php

/*
 * Tento zdrojový kód vychází z výukových seriálů na
 * IT sociální síti WWW.ITNETWORK.CZ
 */

declare(strict_types=1);

namespace App\CoreModule\Presenters;

use App\CoreModule\Model\OnlineManager;
use App\Presenters\BasePresenter;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\ArrayHash;

/**
 * Presenter pro vykreslování příspěvků.
 * @package App\CoreModule\Presenters
 */
class OnlinePresenter extends BasePresenter
{
    /** @var int */
	private $online_id;

    /** @var OnlineManager Model pro správu s příspěvků. */
    public OnlineManager $onlineManager;

    /**
     * Konstruktor s injektovaným modelem pro správu příspěvků.
     * @param OnlineManager $onlineManager    automaticky injektovaný model pro správu příspěvků
     */
    public function __construct(OnlineManager $onlineManager)
    {
        parent::__construct();
        $this->onlineManager = $onlineManager;
    }

	public function actionDetail(int $id) {
		$this->online_id = $id;
	}

    /**
     * Načte a předá příspěvek do šablony podle jeho TITLE.
     * @param string|null $titulek TITLE příspěvku
     * @throws BadRequestException Jestliže příspěvek s danou TITLE nebyl nalezen.
     */

    public function renderDefault(string $titulek = null)
    {
        $products = $this->onlineManager->getOnlines();
        $this->template->onlines = $products;
    }

	public function renderDetail(int $id) {
		try {
			$this->template->online = $this->onlineManager->getOnlineFromId($id);
		} catch (\Exception $e) {
			$this->error('Příspěvek nebyl nalezen');
		}
	}

    /** Načte a předá seznam příspěvků do šablony. */
    public function renderList()
    {
        $this->template->onlines = $this->onlineManager->getAllOnlines();
    }
    
    /**
     * Odstraní příspěvek.
     * @param string|null $titulek TIT příspěvku
     * @throws AbortException
     */
    public function actionRemove(string $titulek = null)
    {
        $this->onlineManager->removeOnline($titulek);
        $this->flashMessage('Příspěvek byl úspěšně odstraněn.');
        $this->redirect('Online:');
    }

    /**
     * Vykresluje formulář pro editaci příspěvku podle zadané TITULEK.
     * Pokud TITULEK není zadán, nebo příspěvek s daným TITULKEM neexistuje, vytvoří se nový.
     * @param string|null $titulek TITULEK adresa příspěvku
     */
    public function actionEditor(string $online_id = null)
    {
        if ($online_id) {
            if (!($online = $this->onlineManager->getOnline($online_id)))
                $this->flashMessage('Příspěvek nebyl nalezen.'); // Výpis chybové hlášky.
            else {$this['editorForm']->setDefaults($online); // Předání hodnot příspěvku do editačního formuláře.
            //pro potřeby náhledu thumb obr v editoru
            $id = $online->online_id;
            }
        }else $id = 0;
        $this->template->id = $id;
    }

    /**
     * Vytváří a vrací formulář pro editaci příspěvků.
     * @return Form formulář pro editaci příspěvků
     */
    protected function createComponentEditorForm()
    {
        // Vytvoření formuláře a definice jeho polí.
        $form = new Form;
        $form->addHidden('online_id');
        $form->addText('titulek', 'Titulek')->setRequired();
        $form->addText('popisek', 'Popisek')->setRequired();
        $form->addUpload('picture', 'Obrázek:')
                ->setRequired(false)
                ->addCondition(Form::FILLED)
                ->addRule(Form::IMAGE);
        $form->addText('web', 'Web');
        $form->addText('odkaz', 'Přesné znění odkazu');
        $form->addTextArea('obsah', 'Obsah');
        $form->addSubmit('save', 'Uložit příspěvek');

        // Funkce se vykonaná při úspěšném odeslání formuláře a zpracuje zadané hodnoty.
        $form->onSuccess[] = function (Form $form, ArrayHash $values) {
            try {
                $this->onlineManager->saveOnline($values);
                $this->flashMessage('příspěvek byl úspěšně uložen.');
                $this->redirect('Online:', $values->titulek);
            } catch (UniqueConstraintViolationException $e) {//otázka jestli de odchytávat podle titulek pak bych to asi vyhodil!
                $this->flashMessage('Příspěvek s touto URL/TITLE adresou již existuje.');
            }
        };

        return $form;
    }
}