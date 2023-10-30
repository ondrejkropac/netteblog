<?php

declare(strict_types=1);

namespace App\CoreModule\Presenters;

use App\CoreModule\Model\ClanekManager;
use App\CoreModule\Model\OnlineManager;
use App\CoreModule\Model\TodoManager;
use App\CoreModule\Model\EventManager;
use App\CoreModule\Model\BlogGalerieManager;
use App\CoreModule\Model\ProfileManager;
use App\CoreModule\Model\TaskerManager;
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
class ListPresenter extends BasePresenter
{

    /** @var ZapisnikManager Model pro správu s článků. */
    public ZapisnikManager $zapisnikManager;
    public TaskerManager $taskerManager;
    
    /** @var ClanekManager Model pro správu s článků. */
    public ClanekManager $clanekManager;
    
    /** @var OnlineManager Model pro správu s článků. */
    public OnlineManager $onlineManager;    

    /** @var TodoManager Model pro správu s článků. */
    public TodoManager $todoManager;
        
    /** @var EventManager Model pro správu s článků. */
    public EventManager $eventManager;

    /**
     * Konstruktor s nastavením URL výchozího článku a injektovaným modelem pro správu článků.
     * @param ZapisnikManager $zapisnikManager    automaticky injektovaný model pro správu článků
     */
    public function __construct(ZapisnikManager $zapisnikManager, TaskerManager $taskerManager, ClanekManager $clanekManager, OnlineManager $onlineManager, TodoManager $todoManager, EventManager $eventManager)
    {
        parent::__construct();
        $this->zapisnikManager = $zapisnikManager;
        $this->taskerManager = $taskerManager;
        $this->clanekManager = $clanekManager;
        $this->onlineManager = $onlineManager;
        $this->todoManager = $todoManager;
        $this->eventManager = $eventManager;
    }

    /**
     * Načte a předá zápisek do šablony podle jeho URL.
     * @param string|null $url URL článku
     * @throws BadRequestException Jestliže zápisek s danou URL nebyl nalezen.
     */

    public function renderDefault(string $url = null)
    {   
        $this->template->clanky = $this->clanekManager->getAllClanky($old=null);

        $this->template->onlines = $this->onlineManager->getOnlines();

        $this->template->zapisky = $this->zapisnikManager->getAllZapisky();
                
        $this->template->tasks = $this->taskerManager->getAllTasks();

        $this->template->todos = $this->todoManager->getAllTodos();

        $this->template->events = $this->eventManager->getAllEvents();
    }
}