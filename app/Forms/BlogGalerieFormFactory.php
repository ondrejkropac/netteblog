<?php

declare(strict_types=1);

namespace App\Forms;

use App\CoreModule\Model\CategoryManager;
use App\CoreModule\Model\BlogGalerieManager;
use Nette\Application\UI\Form;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Http\FileUpload;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;
use App\Forms\FormFactory;


class BlogGalerieFormFactory
{
	use SmartObject;

	/** @var FormFactory Továrnička pro společné formuláře. */
	private FormFactory $formFactory;

	/** @var BlogGalerieManager Model pro produkty. */
	private BlogGalerieManager $bloggalerieManager;

	/** @var CategoryManager Model pro kategorie. */
	private CategoryManager $categoryManager;

	/**
	 * Konstruktor BlogGalerieFormFactory.
	 *
	 * @param \App\Forms\FormFactory $formFactory Továrnička pro společné formuláře.
	 * @param \App\CoreModule\Model\BlogGalerieManager Model pro produkty.
	 * @param \App\CoreModule\Model\CategoryManager Model pro kategorie.
	 */
	public function __construct(
		FormFactory $formFactory,
		BlogGalerieManager $bloggalerieManager,
		CategoryManager $categoryManager
	) {
		$this->formFactory = $formFactory;
		$this->bloggalerieManager = $bloggalerieManager;
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
		$form->addHidden('bloggal_id');
		$form->addText('url', 'URL adresa')->setRequired()
			// Form::URL funguje pouze pro absolutní adresy.
			/*->addRule(Form::PATTERN, null, '[A-Za-z0-9\\-]+')*/;
		$form->addText('titulek', 'Název produktu')->setRequired();
		$form->addText('popisek', 'Krátký popis produktu');
		$form->addMultiUpload('images', 'Obrázky')->setHtmlAttribute('accept', 'image/*')
			->setRequired(false)
			->addRule(Form::IMAGE, 'Formát jednoho nebo více obrázků není podporován.');
		$form->addSubmit('submit', 'Odeslat');
		$form->onSuccess[] = function ($form, $values) use ($onSuccess, $onUrlTaken) {
			$this->blogGalerieFormSucceeded($form, $values, $onSuccess, $onUrlTaken);
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
	public function blogGalerieFormSucceeded($form, $values, $onSuccess, $onUrlTaken)
	{
		// Převede pole obecných souborů na pole obrázků.//
		$images = array_map(function (FileUpload $image) {
			return $image->toImage();
		}, $values->images);
		$values->offsetUnset('images');
		$oldGalerieId = (int)($values->bloggal_id);// nebo fcí intval()
		//getBlogGalerieUrlFromId($oldGalerieId); ???
		if ($oldGalerieId) $oldUrl = $this->bloggalerieManager->getBlogGalerieFromId($oldGalerieId, 'url')['url'];
		else $oldUrl = 0;
		try {
			$galerieUrl = $this->bloggalerieManager->saveBlogGalerie($values);

			$this->bloggalerieManager->saveBlogGalerieImages($galerieUrl, $images, $oldUrl);
			$onSuccess($form, $values);
		} catch (UniqueConstraintViolationException $ex) {
			$onUrlTaken($form, $values);
		}
	}
}
