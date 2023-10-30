<?php

declare(strict_types=1);

namespace App\CoreModule\Presenters;

use App\CoreModule\Model\ClanekManager;
use App\CoreModule\Model\OnlineManager;
use App\CoreModule\Model\BlogGalerieManager;
use App\CoreModule\Model\TaskerManager;
use App\CoreModule\Model\ZapisnikManager;
use App\Presenters\BasePresenter;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;

class CreatorPresenter extends BasePresenter
{
        /** @var ZapisnikManager Model pro správu s článků. */
        public ZapisnikManager $zapisnikManager;
        public TaskerManager $taskerManager;
        
        /** @var ClanekManager Model pro správu s článků. */
        public ClanekManager $clanekManager;
        public BlogGalerieManager $bloggalerieManager;
        
        /** @var OnlineManager Model pro správu s článků. */
        public OnlineManager $onlineManager;
    
        /**
         * Konstruktor s nastavením URL výchozího článku a injektovaným modelem pro správu článků.
         * @param ZapisnikManager $zapisnikManager    automaticky injektovaný model pro správu článků
         */
        public function __construct(ZapisnikManager $zapisnikManager, TaskerManager $taskerManager, ClanekManager $clanekManager, OnlineManager $onlineManager, BlogGalerieManager $bloggalerieManager)
        {
            parent::__construct();
            $this->zapisnikManager = $zapisnikManager;
            $this->taskerManager = $taskerManager;
            $this->clanekManager = $clanekManager;
            $this->bloggalerieManager = $bloggalerieManager;
            $this->onlineManager = $onlineManager;
        }
    
        /**
         * Načte a předá zápisek do šablony podle jeho URL.
         * @param string|null $url URL článku
         * @throws BadRequestException Jestliže zápisek s danou URL nebyl nalezen.
         */
    
        public function renderDefault()
        {   
        $this->template->clanky = $this->clanekManager->getAllClanky();        
        $this->template->neschvalene = (count($this->clanekManager->getApproveClanky(false)));
        $this->template->lastonline = $this->onlineManager->getOnlinePosledni();
        $this->template->onlines = $this->onlineManager->getOnlines();

        $last = $this->clanekManager->getClankyPosledni(!($this->getUser()->isLoggedIn()))->toArray();
        $this->template->posledni = $last;
    
        $this->template->zapisky = $this->zapisnikManager->getAllZapisky();                    
        $this->template->tasks = $this->taskerManager->getAllTasks();            
        $this->template->bloggaleries = $this->bloggalerieManager->getAllBlogGaleries();
    
        }
}
