<?php

declare(strict_types=1);

namespace App\CoreModule\Presenters;

use App\CoreModule\Model\TaskerManager;
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
class TaskerPresenter extends BasePresenter
{
    /** @var int */
	private $kroky_id;

    /** @var TaskerManager Model pro správu s článků. */
    public TaskerManager $taskerManager;

    /**
     * Konstruktor s nastavením ID výchozího článku a injektovaným modelem pro správu článků.
     * @param TaskerManager $taskerManager    automaticky injektovaný model pro správu článků
     */
    public function __construct(TaskerManager $taskerManager)
    {
        parent::__construct();
        $this->taskerManager = $taskerManager;
    }


	public function actionDetail(int $id) {
		$this->kroky_id = $id; //tasker_id
	}

    /**
     * Načte a předá zápisek do šablony podle jeho ID.
     * @param string|null $id ID článku
     * @throws BadRequestException Jestliže zápisek s danou ID nebyl nalezen.
     */

    public function renderDefault(string $id = null)
    {        
        $products = $this->taskerManager->getAllTasks();
        $this->template->tasks = $products;
    }

	public function renderDetail(int $id) {
		try {
			$this->template->task = $this->taskerManager->getTaskFromId($id);
		} catch (\Exception $e) {
			$this->error('Zápisek nebyl nalezen');
		}
	}

    /** Načte a předá seznam článků do šablony. */
    public function renderList()
    {
        $this->template->tasks = $this->taskerManager->getAllTasks();
    }
    
    /**
     * Odstraní zápisek.
     * @param string|null $id ID článku
     * @throws AbortException
     */
    public function actionRemove(string $id = null)
    {
        $this->taskerManager->removeTask($id);
        $this->flashMessage('Zápisek byl úspěšně odstraněn.');
        $this->redirect('Tasker:');
    }

    /**
     * Vykresluje formulář pro editaci článku podle zadané ID.
     * Pokud ID není zadána, nebo zápisek s danou ID neexistuje, vytvoří se nový.
     * @param string|null $id ID adresa článku
     */
    public function actionEditor(string $id = null, $editace = null)
    {
        ($this->getUser()->isLoggedIn());
        if ($id) {
            if (!($task = $this->taskerManager->getTask($id)))
                $this->flashMessage('Zápisek nebyl nalezen.'); // Výpis chybové hlášky.
            else {
            
            $nameofnotes = array(0, 0, 0, 0, 0);
            if ((isset($task['notes']))&(!empty($task['notes']))){//!=''
                if($task['category']=='riders') {$nameofnotes = array('jmeno', 'kolo', 'lokace', 'odkaz', 'charakter');
                    $editace =  'rider';
                }
                if($task['category']=='kalendar') {$nameofnotes = array('jmeno', 'datum', 'lokace', 'odkaz', 0);
                    $editace =  'kalendar';
                }

                $polenotes = explode(',', $task['notes']);
                for( $i=0 ; $i < 5 ; $i++ ){
                    $values[$nameofnotes[$i]] = $polenotes[$i];
                }
            }
            else $values = array(0, 0, 0, 0, 0);
                $this['editorForm']->setDefaults($values);
                $this['editorForm']->setDefaults($task); // Předání hodnot tasku do editačního formuláře.
            }
        }
    }


    /**
     * Vytváří a vrací formulář pro editaci článků.
     * @return Form formulář pro editaci článků
     */
    protected function createComponentEditorForm()
    {
        $parameters = $this->getParameters();
        if (!isset($parameters['editace'])) $parameters['editace'] = false;
        // Vytvoření formuláře a definice jeho polí.
        $contentType = [
            'cal' => 'Do kalendáře',
            'rid' => 'Gravelistu',
        ];
        $categories = [
				'web design' => 'todo',
				'návrhy na rozvoj' => 'ideas',
				'nový obsah' => 'redakce',
				'nový přispěvatelé' => 'riders',
				'nové události' => 'kalendar',
            ];
            $categories = array_flip($categories);
        
        $form = new Form;
        $form->addHidden('tasker_id'); //tasks !!
        $form->addHidden('notes');
        $form->addText('titulek', 'Titulek')->setRequired();
        $form->addText('autor', 'Autor')->setRequired();
        $form->addText('popisek', 'Popisek')->setRequired();
        if (($parameters['editace'] == 'rider')) { //echo('rider');
            $form->addText('jmeno', 'Jméno gravelisty');//->setRequired();
            $form->addText('kolo', 'Kolo');//->setRequired();
            $form->addText('lokace', 'Lokace');//->setRequired();
            $form->addText('odkaz', 'Odkaz ');//->setRequired();
            $form->addText('charakter', 'Charakteristika');
            $form->addHidden('category');
            }
        elseif ($parameters['editace'] == 'kalendar') {
            $form->addText('jmeno', 'Název události');//->setRequired();
            $form->addText('datum', 'Datum');//->setRequired();
            $form->addText('lokace', 'Lokace');//->setRequired();
            $form->addText('odkaz', 'Odkaz');//->setRequired();
            $form->addHidden('category');
        }else{
        //if (($task->category == 'riders')||($task->category == 'kalendar'))
        $content = $form->addSelect('content', 'Chceš zapsat:', $contentType);
            $form->addSubmit("choose", "Rozšiř formulář");
            $form->addSelect('category', 'Kategorie:', $categories)
                ->setPrompt('Zvolte kategorii');
        }
        $form->addTextArea('obsah', 'Obsah');
        $form->addSubmit('save', 'Uložit zápisek');

        // Funkce se vykonaná při úspěšném odeslání formuláře a zpracuje zadané hodnoty.
        $form->onSuccess[] = function (Form $form, ArrayHash $values) {
            
            if (!$values['tasker_id'])
            $values['autor'] = $this->user->identity->username;
            
            if ($form['save']->isSubmittedBy()){
                try {
                    $this->readNotes($values);
                    $this->taskerManager->saveTask($values);
                    $this->flashMessage('Zápis byl úspěšně uložen.');
                    $this->redirect('Tasker:', $values->tasker_id);
                } catch (UniqueConstraintViolationException $e) {
                    $this->flashMessage('Zápis s touto ID adresou již existuje.');
                }
            }
            if ($form['choose']->isSubmittedBy()){
                if ($values['content'] == 'rid'){
                    $editace = 'rider';
                    $values['category'] = 'riders';
                }
                elseif ($values['content'] == 'cal'){
                    $editace = 'kalendar';
                    $values['category'] = 'kalendar';
                }
                
                $this->taskerManager->saveTask($values);//zvaž jiný postup než  ukládání do DB, ale potřebuju to do function readNotes!
                $this->redirect('Tasker:editor', $values->tasker_id, $editace);
            }
        };

        return $form;
    }

    private function readNotes($values=null)
    {        
        if (!empty($task['notes'])){
            if ($values['category'] == 'riders') $nameofnotes = array('jmeno', 'kolo', 'lokace', 'charakter', 'odkaz');
            elseif ($values['category'] == 'kalendar') $nameofnotes = array('jmeno', 'datum', 'lokace', 'odkaz');
                for( $i=0 ; $i < 5 ; $i++ ){
                    $note[] = $values[$nameofnotes[$i]];
                    unset($values[$nameofnotes[$i]]);
                }
                $notes = (implode(",", $note));
                $values['notes'] = $notes;
            }
    }
    
    /**
     * Vytváří a vrací komponentu pro zobrazení a manipulaci s již nahranými obrázky u produktu.
     * @return TaskerContentControl komponenta pro zobrazení a manipulaci s již nahranými obrázky u produktu
     */
    protected function createComponentTaskerContent()
    {
        return new TaskerContentControl($this->taskerManager);
    }
    
    /**
     * Správa produktu.
     * @param string $id ID adresa produktu, který editujeme; pokud není zadána, přidá se produktu jako nový
     */
    public function actionManage($id)
    {
        if ($id && ($product = $this->taskerManager->getTask($id))) {
            $task = $product->toArray(); // IRow je read-only, pro manipulaci s ním ho musíme převést na pole.
            $this['editorForm']->setDefaults($task); // 'bloggalerie'
            $this['taskerContent']->setTasker($product); // Nastaví již existující produkt do komponenty.
        }
    }

    /**
     * Správa produktu.
     * @param string $id ID adresa produktu, který editujeme; pokud není zadána, přidá se produktu jako nový
     */
    public function renderManage($id)
    {
        $this->template->title = $id ? 'Editace produktu' : 'Nový produkt';// Definuje komponentu, která se vykreslí v rámci formuláře.
        $this->template->taskerContentWidget = ArrayHash::from(['name' => 'taskerContent', 'after' => 'popisek']);
    }
}

    // bin default

    
        /*$parameters = $this->getParameters();
        if ($id && ($category = $this->categoryManager->getCategory($id))) {
            $parameters['category_id'] = $category->id;
        } elseif ($id) {
            $this->flashMessage('Zvolená kategorie neexistuje.');
            $this->redirect('Tasker');
        }
        $products = $this->taskerManager->getTasksKategorie($parameters);
        $this->template->categoryName = isset($category) ? $category->name : 'Zápisky';*/

        // actionEditor

        
            /*for( $i=0 ; $i < 5 ; $i++ ){
                $values[$nameofnotes[$i]] = $polenotes[$i];
            }*/
                // než se naučím rozumě načítat hodnoty do formuláře stačí mi tady způsob s náhradním pole, 
                //kde jeho klíče odpovídající hodnotám inputu formu nakrmí form pro editaci, tam, kde stávající Active raw pole z DB nelze přepisovat
                //$this->template->editace = $editace; //!!! ještě neumím předat hodnotu editace do formu abych pro už zvolenou kategorii nezobrazoval form s hodnotami jiné rider/kalendar
                
                //$this['editorForm']->setTasker($task); - HA jak dostat do formu hodnotu aktuální kategorie abych pro rdrs a kal nenabízel select kategorie!

                // readNotes

                
        /*if (!empty($values['kolo'])){
            $ridernotes = ($values['jmeno']. $values['kolo']. $values['lokace']. $values['charakter']. $values['odkaz']);
        }
        elseif(!empty($values['datum'])){
            $kalendarnotes = ($values['jmeno']. $values['datum']. $values['lokace']. $values['odkaz']);
        }*/ //celý navíc!
        
        //actionManage

        
            //$bloggalerie['categories'] = $this->categoryManager->getBloggalerieCategories($pro->bloggal_id);