<?php

declare(strict_types=1);

namespace App\CoreModule\Presenters;

use App\CoreModule\Model\TodoManager;
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
class TodoPresenter extends BasePresenter
{
    /** @var int */
	private $todo_id;

    /** @var TodoManager Model pro správu s článků. */
    public TodoManager $todoManager;

    /**
     * Konstruktor s nastavením URL výchozího článku a injektovaným modelem pro správu článků.
     * @param TodoManager $todoManager    automaticky injektovaný model pro správu článků
     */
    public function __construct(TodoManager $todoManager)
    {
        parent::__construct();
        $this->todoManager = $todoManager;
    }


	public function actionDetail(int $id) {
		$this->todo_id = $id; //todo_id
	}

    /**
     * Načte a předá zápisek do šablony podle jeho URL.
     * @param string|null $url URL článku
     * @throws BadRequestException Jestliže zápisek s danou URL nebyl nalezen.
     */

    public function renderDefault(string $url = null)
    {
        $this->template->todos = $this->todoManager->getAllTodos();
    }
    
    public function renderList(string $url = null)
    {
        $this->template->todos = $this->todoManager->getAllTodos();
    }

	public function renderDetail(int $id) {
		try {
			$this->template->todo = $this->todoManager->getTodoFromId($id);
		} catch (\Exception $e) {
			$this->error('Zápisek nebyl nalezen');
		}
	}
    
    /**
     * Odstraní zápisek.
     * @param string|null $url URL článku
     * @throws AbortException
     */
    public function actionRemove(string $todo_id = null)
    {
        $this->todoManager->removeTodo($todo_id);
        $this->flashMessage('Zápisek byl úspěšně odstraněn.');
        $this->redirect('Todo:');
    }

    /**
     * Vykresluje formulář pro editaci článku podle zadané TODO_ID.
     * Pokud TODO_ID není zadána, nebo zápisek s danou TODO_ID neexistuje, vytvoří se nový.
     * @param string|null $todo_id TODO_ID adresa článku
     */
    public function actionEditor(int $todo_id = null)
    {
        if ($todo_id) {
            if (!($todo = $this->todoManager->getTodo($todo_id)))
                $this->flashMessage('Zápisek nebyl nalezen.'); // Výpis chybové hlášky.
            else $this['editorForm']->setDefaults($todo); // Předání hodnot článku do editačního formuláře.

            $this->template->todo_id = $todo_id;
        }
    }

    public function handleInwork(int $id = null) {
        $inwork= $this->todoManager->getStav($id);
        if (($inwork=='')||($inwork=='stop')) {
            $stav = 'iw';
            $this->flashMessage('Rozpracováno.');
        } elseif ($inwork=='iw'){
            $stav = 'stop';
            $this->flashMessage('Pozastaveno.');
        }
        $this->todoManager->inworkTodo($id, $stav);
        $this->redirect('Todo:list');
    }

    public function handleFinal(int $id = null) {
            $stav = 'done';
            //pokud je součástí vývoje - dev = 1, měla by při schválení vyskočit poznámky k dokumentaci pro vývoj...
            $this->flashMessage('Dokončeno.');
        $this->todoManager->inworkTodo($id, $stav);
        $this->redirect('Todo:list');
    }

    public function handleDev(int $id = null) {
        $dev = true;
        $this->flashMessage('Rařazeno do vývoje.');
    $this->todoManager->devTodo($id, $dev);
    $this->redirect('Todo:list');
    }
    
    /**
     * Vytváří a vrací formulář pro editaci článků.
     * @return Form formulář pro editaci článků
     */
    protected function createComponentEditorForm()
    {
        // Vytvoření formuláře a definice jeho polí.
        $form = new Form;
        $form->addHidden('todo_id'); //todos
        $form->addHidden('autor'); 
        $form->addText('nazev', 'Titulek')->setRequired();
        $form->addText('stav', 'Popisek')->setRequired();
        $form->addTextArea('notes', 'Obsah');
        $form->addSubmit('save', 'Uložit zápisek');

        // Funkce se vykonaná při úspěšném odeslání formuláře a zpracuje zadané hodnoty.
        $form->onSuccess[] = function (Form $form, ArrayHash $values) {
            //uložení přiklášeného jako autora při odeslání nového todo!
            if (!$values['todo_id'])
            $values['autor'] = $this->user->identity->username;
            
            try {
                $this->todoManager->saveTodo($values);
                $this->flashMessage('Zápis byl úspěšně uložen.');
                $this->redirect('Todo:');//, $values->todo_id
            } catch (UniqueConstraintViolationException $e) {
                $this->flashMessage('Zápis s touto URL adresou již existuje.');
            }
        };

        return $form;
    }
}