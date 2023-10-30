<?php

declare(strict_types=1);

namespace App\CoreModule\Presenters;

use App\CoreModule\Model\EventManager;
use App\Presenters\BasePresenter;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Application\UI\Form;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Utils\ArrayHash;
use Nette\Security\User;
use App\Model\UserManager;

/**
 * Presenter pro vykreslování článků.
 * @package App\CoreModule\Presenters
 */
class EventPresenter extends BasePresenter
{
    /** @var int */
	private $event_id;

    /** @var User Model pro přístup k atuálnímu uživateli. */
    private User $user;

    /** @var EventManager Model pro správu s článků. */
    public EventManager $eventManager;

    /** @var UserManager Model pro správu uživatelů. */
    private UserManager $userManager;

    /**
     * Konstruktor s nastavením URL výchozího článku a injektovaným modelem pro správu článků.
     * @param EventManager $eventManager    automaticky injektovaný model pro správu článků
     */
    public function __construct(EventManager $eventManager, User $user, UserManager $userManager)
    {
        parent::__construct();
        $this->eventManager = $eventManager;
        $this->user = $user;
        $this->userManager = $userManager;
    }


	public function actionDetail(int $id) {
		$this->event_id = $id; //event_id
	}

    /**
     * Načte a předá zápisek do šablony podle jeho URL.
     * @param string|null $url URL článku
     * @throws BadRequestException Jestliže zápisek s danou URL nebyl nalezen.
     */

    public function renderDefault(string $url = null)
    {
        $products = $this->eventManager->getAllEvents();
        $this->template->events = $products;
    }

	public function renderDetail(int $id) {
		try {
			$this->template->event = $this->eventManager->getEventFromId($id);
		} catch (\Exception $e) {
			$this->error('Zápisek nebyl nalezen');
		}
	}
    
    /**
     * Odstraní zápisek.
     * @param string|null $url URL článku
     * @throws AbortException
     */
    public function actionRemove(string $event_id = null)
    {
        $this->eventManager->removeEvent($event_id);
        $this->flashMessage('Událost byla úspěšně odstraněna.');
        $this->redirect('Event:');
    }

    /**
     * Vykresluje formulář pro editaci článku podle zadané EVENT_ID.
     * Pokud EVENT_ID není zadána, nebo zápisek s danou EVENT_ID neexistuje, vytvoří se nový.
     * @param string|null $event_id EVENT_ID adresa článku
     */
    public function actionEditor(int $event_id = null)
    {
        $_SESSION['class'] = 'Event:';
        ($this->getUser()->isLoggedIn());
        $this->template->username = $this->user->identity->username;
        
        if ($event_id) {
            if (!($event = $this->eventManager->getEvent($event_id)))
                $this->flashMessage('Událost nebyla nalezena.'); // Výpis chybové hlášky.
            else $this['editorForm']->setDefaults($event); // Předání hodnot článku do editačního formuláře.

            $this->template->event_id = $event_id;
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
        $form->addHidden('event_id'); //events
        $form->addText('nazev', 'Titulek')->setRequired();
        $form->addText('misto', 'Popisek místa konání')->setRequired();
        $form->addText('datum_konani', 'Termím události');
        $form->addText('web', 'Web události');
        $form->addText('odkaz', 'Odkaz na bližší informace');
        $form->addTextArea('propozice', 'Obsah');
        $form->addSubmit('save', 'Uložit zápisek');

        // Funkce se vykonaná při úspěšném odeslání formuláře a zpracuje zadané hodnoty.
        $form->onSuccess[] = function (Form $form, ArrayHash $values) {

        // K recenzi události ID aktuálně přihlášeného uživatele.
        $id =  $this->user->getId();
        $name = ($this->userManager->userName($id))->toArray();
        $values['autor'] = $name['username'];
            try {
                $this->eventManager->saveEvent($values);
                $this->flashMessage('Zápis byl úspěšně uložen.');
                $this->redirect('Event:');//, $values->event_id
            } catch (UniqueConstraintViolationException $e) {
                $this->flashMessage('Zápis s touto URL adresou již existuje.');
            }
        };

        return $form;
    }
}