<?php

declare(strict_types=1);

namespace App\CoreModule\Presenters;

use App\CoreModule\Model\TaskerManager;
use App\Presenters\BasePresenter;
use Nette\Application\AbortException;
use Nette\Application\BadRequestException;
use Nette\Utils\ArrayHash;
use Nette\Application\UI\Form;

/**
 * Presenter pro vykreslování článků.
 * @package App\CoreModule\Presenters
 */
class RidersPresenter extends BasePresenter
{
    /** pole pole uživatelů pro správu článků. */
    private $popisek = ['Aleš Bican','Jan Brenk','Jan Reiner','Marek Babál','Michal Soukup','Michal Střecha','Mirek Navrátil','Ondřej Horák','Standa Špiler','Tomáš Bendář','Zbyněk Prudil','Zdeněk Pracný','Zdeněk Vývoda','Jan Syrový','Michal Pavliska','Pavel Kovalančík','Petr Zoubek','Dagmar Asaová', ''];
    
    /** @var TaskerManager Model pro správu článků. */
    public TaskerManager $taskerManager;

    /**
     * Konstruktor s nastavením URL výchozího článku a injektovaným modelem pro správu článků.
     * @param TaskerManager $taskerManager    automaticky injektovaný model pro správu článků
     */
    public function __construct(TaskerManager $taskerManager)
    {
        parent::__construct();
        $this->taskerManager = $taskerManager;
    }

	public function actionDetail(int $id) {        //$popisRidera
        $popisRidera[1] = ['Scott','Amman/Tmaň','runner'];
        $popisRidera[2] = ['Factot/Spec','Olomouc','ČS team'];
        $popisRidera[6] = ['3T','Brno','vytrvalec'];
        $popisRidera[11] = ['Canyon','Příbram','krajinář'];
        $popisRidera[12] = ['Canyon','Morava','vrchař'];
        $popisRidera[13] = ['Cervélo','Praha','kafe pivo gravel'];
        $popisRidera[14] = ['Szutrówka','Beskydy','srdcem'];
        $popisRidera[15] = ['Race Bike','Chomutov','metal!'];
        $popisRidera[16] = ['Trek','Mutěnice','couračka jihem Moravy'];
        $popisRidera[17] = ['Kellys','Slovensko','letem i ledem'];
        
        $popis = ['Bike','Lokace','Charakteristika'];
        
        $popisek = ['Aleš Bican','Jan Brenk','Jan Reiner','Marek Babál','Michal Soukup','Michal Střecha','Mirek Navrátil','Ondřej Horák','Standa Špiler','Tomáš Bendář','Zbyněk Prudil','Zdeněk Pracný','Zdeněk Vývoda','Jan Syrový','Michal Pavliska','Pavel Kovalančík','Petr Zoubek','Dagmar Asaová', ''];
        $this->template->popisek = $popisek;
        $this->template->popis = $popis;
		$this->template->popisRider = $popisRidera[$id];
        $this->template->detail = $id;
	}

    /**
     * Načte a předá zápisek do šablony podle jeho URL.
     * @param string|null $url URL článku
     * @throws BadRequestException Jestliže zápisek s danou URL nebyl nalezen.
     */

    public function renderDefault()
    {
        $popisek = ['Aleš Bican','Jan Brenk','Jan Reiner','Marek Babál','Michal Soukup','Michal Střecha','Mirek Navrátil','Ondřej Horák','Standa Špiler','Tomáš Bendář','Zbyněk Prudil','Zdeněk Pracný','Zdeněk Vývoda','Jan Syrový','Michal Pavliska','Pavel Kovalančík','Petr Zoubek','Dagmar Asaová', ''];
        $agreed = ['0','1','1','0','0','0','1','0','0','0','0','1','1','1','1','1','1','1'];
        $this->template->popisek = $popisek;
        $this->template->agreed = $agreed;

        $values['category'] = 'riders';
        $this['editorForm']->setDefaults($values);
    }

	public function renderDetail(int $id) {
        
	}
    
        /**
     * Vytváří a vrací formulář pro editaci tasků.
     * @return Form formulář pro editaci tásků
     */
    protected function createComponentEditorForm()
    {
        $form = new Form;
        $form->addHidden('tasker_id');
        $form->addHidden('category');
        $form->addText('titulek', 'Titulek')->setRequired();
        $form->addTextArea('obsah', 'Obsah');
        $form->addSubmit('save', 'Uložit gravelistu');

        // Funkce se vykonaná při úspěšném odeslání formuláře a zpracuje zadané hodnoty.
        $form->onSuccess[] = function (Form $form, ArrayHash $values) {
                try {
                    $this->taskerManager->saveTask($values);
                    $this->flashMessage('Záznam byl úspěšně uložen.');
                    $this->redirect('Tasker:');//, $values->tasker_id
                } catch (UniqueConstraintViolationException $e) {
                    $this->flashMessage('Zápis s touto ID adresou již existuje.');
                }
        };

        return $form;
    }
}