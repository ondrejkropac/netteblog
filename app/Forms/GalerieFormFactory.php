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

namespace App\Forms;

use App\CoreModule\Model\CategoryManager;
use App\CoreModule\Model\GalleryManager;
use Nette\Application\UI\Form;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Http\FileUpload;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use App\Forms\FormFactory;


class GalerieFormFactory
{
	use SmartObject;

	/** @var FormFactory Továrnička pro společné formuláře. */
	private FormFactory $formFactory;

	/** @var GalleryManager Model pro produkty. */
	private GalleryManager $galleryManager;

	/** @var CategoryManager Model pro kategorie. */
	private CategoryManager $categoryManager;

	/**
	 * Konstruktor GalleryFormFactory.
	 *
	 * @param \App\Forms\FormFactory $formFactory Továrnička pro společné formuláře.
	 * @param \App\CoreModule\Model\GalleryManager Model pro produkty.
	 * @param \App\CoreModule\Model\CategoryManager Model pro kategorie.
	 */
	public function __construct(
		FormFactory $formFactory,
		GalleryManager $galleryManager,
		CategoryManager $categoryManager
	) {
		$this->formFactory = $formFactory;
		$this->galleryManager = $galleryManager;
		$this->categoryManager = $categoryManager;
	}

	/**
	 * Vytváří a vrací komponentu formuláře pro správu produktu.
	 * @param callable $onSuccess specifická funkce, která se vykoná po úspěšném odeslání formuláře.
	 * @param callable $onUrlTaken specifická funkce, která se vykoná při zabrané URL adrese.
	 * @return Form Formulář s předpřipravenými vlastnostmi pro produkt.
	 */
	public function create($onSuccess, $onUrlTaken)
	{
		$form = $this->formFactory->create();
		$form->addHidden('gallery_id');
		$form->addHidden('orderGalerie');
		$form->addText('titleGalerie', 'Název obrázku')->setRequired();
		$form->addText('imgFullNameGalerie', 'Jméno souboru')->setRequired();
		$form->addText('popisGalerie', 'Krátký popis obrázku');
		$form->addUpload('picture', 'Obrázek:')
                ->setRequired(false)
                ->addCondition(Form::FILLED)
                ->addRule(Form::IMAGE);
		$form->addSubmit('submit', 'Odeslat');
		$form->onSuccess[] = function ($form, $values) use ($onSuccess, $onUrlTaken) {
			$this->galerieFormSucceeded($form, $values, $onSuccess, $onUrlTaken);
		};
		return $form;
	}

	/**
	 * Funkce se vykonaná při úspěšném odeslání formuláře pro správu produktu.
	 * @param Form $form Formulář pro správu produktů
	 * @param ArrayHash $values Hodnoty formuláře
	 * @param callable $onSuccess Specifická funkce, která se vykoná po úspěšném odeslání formuláře
	 * @param callable $onUrlTaken Specifická funkce, která se vykoná při zabrané URL adrese
	 */
	public function galerieFormSucceeded($form, $values, $onSuccess, $onUrlTaken)
	{ 
		try {
			$galerie_id = $this->galleryManager->saveGallery($values);
			$onSuccess($form, $values);
		} catch (UniqueConstraintViolationException $ex) {// zbytečný sledovat/odchytávat url když mám přiděleno jen Id
			$onUrlTaken($form, $values);
		}
	}
}


	/** bin */
	
		//$form->addText('url', 'URL adresa')->setRequired()
			// Form::URL funguje pouze pro absolutní adresy.
			/*->addRule(Form::PATTERN, null, '[A-Za-z0-9\\-]+');*/

			// create

			
				//->setHtmlAttribute('id', 'files' );
				// kategorie ve třídě gallery pořád zvažuju!
			/*$form->addMultiSelect('categories', 'Kategorie', $this->categoryManager->getCategoryLeafs())
			->setRequired();*/