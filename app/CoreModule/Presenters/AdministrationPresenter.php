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

use App\CoreModule\Model\TaskerManager;
use App\CoreModule\Model\RedactionManager;
use App\Forms\SignInFormFactory;
use App\Forms\SignUpFormFactory;
use App\Presenters\BasePresenter;
use Nette\Application\AbortException;
use Nette\Application\UI\Form;
use Nette\Utils\ArrayHash;

/**
 * Presenter pro vykreslování administrační sekce.
 * @package App\CoreModule\Presenters
 */
class AdministrationPresenter extends BasePresenter
{
    /** @var SignInFormFactory */
    private SignInFormFactory $signInFactory;

    /** @var SignUpFormFactory */
    private SignUpFormFactory $signUpFactory;

    /** @var TaskerManager Model pro správu s článků. */
    public TaskerManager $taskerManager;

    /** @var RedactionManager Model pro správu s článků. */
    public RedactionManager $redactionManager;

    /**
     * AdministrationPresenter constructor.
     * @param SignInFormFactory $signInFactory
     * @param SignUpFormFactory $signUpFactory
     * @param RedactionManager $redactionManager
     */
    public function __construct(SignInFormFactory $signInFactory, SignUpFormFactory $signUpFactory, TaskerManager $taskerManager, RedactionManager $redactionManager)
    {
        parent::__construct();
        $this->signInFactory = $signInFactory;
        $this->signUpFactory = $signUpFactory;
        $this->taskerManager = $taskerManager;
        $this->redactionManager = $redactionManager;
    }

    /**
     * Před vykreslováním stránky pro přihlášení přesměruje do administrace, pokud je uživatel již přihlášen.
     * @throws AbortException Když dojde k přesměrování.
     */
    public function actionLogin($class=null)
    {
        /*$parameters = $this->getParameters();//dump($parameters['class']);
        $this['loginForm']->setDefaults($parameters);*/
        if ($this->getUser()->isLoggedIn()) $this->redirect('Administration:');
    }

    /**
     * Odhlásí uživatele a přesměruje na přihlašovací stránku.
     * @throws AbortException Při přesměrování na přihlašovací stránku.
     */
    public function actionLogout()
    {
        $this->getUser()->logout();
        $this->redirect('login');
    }

    /** Předá jméno přihlášeného uživatele do šablony administrační stránky. */
    public function renderDefault()
    {
        if ($this->getUser()->isLoggedIn())
            $this->template->username = $this->user->identity->username;

        $this->template->redactions = $this->redactionManager->getAllRedactiontasks();
    }

    /**
     * Vytváří a vrací přihlašovací formulář pomocí továrny.
     * @return Form přihlašovací formulář
     */
    protected function createComponentLoginForm()
    { //dump($_SESSION);
        return $this->signInFactory->create(function () {
            $this->flashMessage('Byl jste úspěšně přihlášen.');
            $this->redirect('Administration:');
        });
    }

    /**
     * Vytváří a vrací registrační formulář pomocí továrny.
     * @return Form registrační formulář
     */
    protected function createComponentRegisterForm()
    {
        return $this->signUpFactory->create(function (): void {
            $this->flashMessage('Byl jste úspěšně zaregistrován.');
            $this->redirect('Administration:');
        });
    }

        /**
     * Vytváří a vrací formulář pro editaci tasků.
     * @return Form formulář pro editaci tásků
     */
    protected function createComponentEditorForm()
    {
        $categories = [
				'web design' => 'todo',
				'návrhy' => 'ideas',
				'nový obsah' => 'redakce',
            ];
        $form = new Form;
        $form->addHidden('tasker_id');
        $form->addText('titulek', 'Titulek')->setRequired();
        $form->addText('autor', 'Autor')->setRequired();
        $form->addText('popisek', 'Popisek')->setRequired();
            $form->addSelect('category', 'Kategorie:', $categories)
                ->setPrompt('Zvolte kategorii');
        $form->addTextArea('obsah', 'Obsah');
        $form->addSubmit('save', 'Uložit novou ideu');

        // Funkce se vykonaná při úspěšném odeslání formuláře a zpracuje zadané hodnoty.
        $form->onSuccess[] = function (Form $form, ArrayHash $values) {
            //if ($form['save']->isSubmittedBy()){
                try {
                    $this->taskerManager->saveTask($values);
                    $this->flashMessage('Zápis byl úspěšně uložen.');
                    $this->redirect('Tasker:');//, $values->tasker_id
                } catch (UniqueConstraintViolationException $e) {
                    $this->flashMessage('Zápis s touto ID adresou již existuje.');
                }
            //}
        };

        return $form;
    }
}