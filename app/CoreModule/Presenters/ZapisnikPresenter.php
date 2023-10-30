<?php

declare(strict_types=1);

namespace App\CoreModule\Presenters;

use App\CoreModule\Model\ZapisnikManager;
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
class ZapisnikPresenter extends BasePresenter
{
    /** @var int */
	private $kroky_id;

    /** @var ZapisnikManager Model pro správu s článků. */
    public ZapisnikManager $zapisnikManager;

    /**
     * Konstruktor s nastavením URL výchozího článku a injektovaným modelem pro správu článků.
     * @param ZapisnikManager $zapisnikManager    automaticky injektovaný model pro správu článků
     */
    public function __construct(ZapisnikManager $zapisnikManager)
    {
        parent::__construct();
        $this->zapisnikManager = $zapisnikManager;
    }


	public function actionDetail(int $id) {
		$this->kroky_id = $id; //zapisek_id
	}

    /**
     * Načte a předá zápisek do šablony podle jeho URL.
     * @param string|null $url URL článku
     * @throws BadRequestException Jestliže zápisek s danou URL nebyl nalezen.
     */

    public function renderDefault(string $url = null)
    {
        $products = $this->zapisnikManager->getAllZapisky();
        $this->template->zapisky = $products;
    }

	public function renderDetail(int $id) {
		try {
			$this->template->zapisek = $this->zapisnikManager->getZapisekFromId($id);
		} catch (\Exception $e) {
			$this->error('Zápisek nebyl nalezen');
		}
	}

    /** Načte a předá seznam článků do šablony. */
    public function renderList()
    {
        $this->template->zapisky = $this->zapisnikManager->getAllZapisky();
    }
    
    /**
     * Odstraní zápisek.
     * @param string|null $url URL článku
     * @throws AbortException
     */
    public function actionRemove(string $url = null)
    {
        $this->zapisnikManager->removeZapisek($url);
        $this->flashMessage('Zápisek byl úspěšně odstraněn.');
        $this->redirect('Zapisnik:');
    }

    /**
     * Vykresluje formulář pro editaci článku podle zadané URL.
     * Pokud URL není zadána, nebo zápisek s danou URL neexistuje, vytvoří se nový.
     * @param string|null $url URL adresa článku
     */
    public function actionEditor(string $url = null)
    {
        if ($url) {
            if (!($zapisek = $this->zapisnikManager->getZapisek($url)))
                $this->flashMessage('Zápisek nebyl nalezen.'); // Výpis chybové hlášky.
            else $this['editorForm']->setDefaults($zapisek); // Předání hodnot článku do editačního formuláře.
        }
    }

    /**
     * Vytváří a vrací formulář pro editaci článků.
     * @return Form formulář pro editaci článků
     */
    protected function createComponentEditorForm()
    {
        // Vytvoření formuláře a definice jeho polí.
        $form = new Form;
        $form->addHidden('kroky_id'); //zapisky
        $form->addText('titulek', 'Titulek')->setRequired();
        $form->addText('url', 'URL')->setRequired();
        $form->addText('popisek', 'Popisek')->setRequired();
        $form->addText('klicova_slova', 'Klicova_slova');
        $form->addTextArea('obsah', 'Obsah');
        $form->addSubmit('save', 'Uložit zápisek');

        // Funkce se vykonaná při úspěšném odeslání formuláře a zpracuje zadané hodnoty.
        $form->onSuccess[] = function (Form $form, ArrayHash $values) {
            try {
                $this->zapisnikManager->saveZapisek($values);
                $this->flashMessage('Zápis byl úspěšně uložen.');
                $this->redirect('Zapisnik:detail', $values->kroky_id);
            } catch (UniqueConstraintViolationException $e) {
                $this->flashMessage('Zápis s touto URL adresou již existuje.');
            }
        };

        return $form;
    }
}