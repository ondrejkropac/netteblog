<?php

declare(strict_types=1);

namespace App\CoreModule\Presenters;

use App\CoreModule\Model\ProfileManager;
use App\Presenters\BasePresenter; //??
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\ArrayHash;

/**
 * Presenter pro vykreslování článků.
 * @package App\CoreModule\Presenters
 */
class ProfilePresenter extends BasePresenter
{
    /** @var int */
	private $profile_id;

    /** @var ProfileManager Model pro správu s článků. */
    public ProfileManager $profileManager;

    /**
     * Konstruktor s nastavením URL výchozího článku a injektovaným modelem pro správu článků.
     * @param string         $defaultProfileUrl URL výchozího článku
     */
    public function __construct(ProfileManager $profileManager)
    {
        parent::__construct();
        $this->profileManager = $profileManager;
    }

	public function actionDetail(int $id) {
		$this->profile_id = $id;
	}

    /**
     * Načte a předá článek do šablony podle jeho URL.
     * @param string|null $url URL článku
     * @throws BadRequestException Jestliže článek s danou URL nebyl nalezen.
     */

    public function renderDefault(string $url = null)
    {
        $profiles = $this->profileManager->getProfiles();
        $this->template->profiles = $profiles;
    }

	public function renderDetail(int $id) {
		try {
			$this->template->profile = $this->profileManager->getProfile($id);
		} catch (\Exception $e) {
			$this->error('Článek nebyl nalezen');
		}
	}

    /** Načte a předá seznam článků do šablony. */
    public function renderList()
    { 
        $this->template->profiles = $this->profileManager->getProfiles();
    }
    
    /**
     * Odstraní článek.
     * @param string|null $url URL článku
     * @throws AbortException
     */
    public function actionRemove(string $id = null)
    {
        $this->profileManager->removeProfile($id);
        $this->flashMessage('Článek byl úspěšně odstraněn.');
        $this->redirect('Profile:list');
    }

    /**
     * Vykresluje formulář pro editaci článku podle zadané URL.
     * Pokud URL není zadána, nebo článek s danou URL neexistuje, vytvoří se nový.
     * @param string|null $url URL adresa článku
     */
    public function actionEditor(string $id = null)
    {
        $this->getUser()->getId();
        if ($id) {
            if (!($profile = $this->profileManager->getProfile($id)))
                $this->flashMessage('Článek nebyl nalezen.'); // Výpis chybové hlášky.
            else {
                if ($this->getUser()->isLoggedIn())
                    $this->template->username = $this->user->identity->username;
                $this->template->uzivatel = $profile->jmeno;
                $this['editorForm']->setDefaults($profile); // Předání hodnot článku do editačního formuláře.
            }
            //pro potřeby náhledu thumb obr v editoru
            $this->template->id = $id;
        }
        $this->template->id = '';$this->template->username = '';$this->template->uzivatel = '';
    }

    /**
     * Vytváří a vrací formulář pro editaci článků.
     * @return Form formulář pro editaci článků
     */
    protected function createComponentEditorForm()
    {
        // Vytvoření formuláře a definice jeho polí.
        $form = new Form;
        $form->addHidden('jmeno');
        $form->addHidden('profile_id');
        $form->addText('kolo', 'Kolo')->setRequired();
        $form->addText('lokace', 'Lokalita')->setRequired();
        $form->addUpload('picture', 'Profilový obrázek:')
                ->setRequired(false)
                ->addCondition(Form::FILLED)
                ->addRule(Form::IMAGE);
        $form->addUpload('picture_bike', 'Kolo:')
                ->setRequired(false)
                ->addCondition(Form::FILLED)
                ->addRule(Form::IMAGE);
        $form->addText('role', 'Role uživatele')->setRequired();
        $categories = $this->categoryManager->getAllCategoryName();
        $form->addSubmit('save', 'Uložit profil');

        // Funkce se vykonaná při úspěšném odeslání formuláře a zpracuje zadané hodnoty.
        $form->onSuccess[] = function (Form $form, ArrayHash $values) {

            //$name = $this->userManager->userName($id)->toArray();
            ($this->getUser()->isLoggedIn());
            if (!$values['profile_id'])
            $values['jmeno'] = $this->user->identity->username;//$name['username'];// jen při prvním editu - založení karty edit autora rozhodně ne!
            
            try {
                $this->profileManager->saveProfile($values);
                $this->flashMessage('Profil byl úspěšně uložen.');
                    
                if (isset($values['profile_id']))
                    $this->redirect('Profile:detail', $values->profile_id);
                else
                    $this->redirect('Profile:'); // smazáno, $values->url
            } catch (UniqueConstraintViolationException $e) {
                $this->flashMessage('Profil s touto URL adresou již existuje.'); // dolaď
            }
        };

        return $form;
    }
}