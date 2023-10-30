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

namespace App\Forms;

use App\CoreModule\Model\GalleryManager;
use App\CoreModule\Model\ReviewManager;
use Nette\Application\UI\Form;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Security\User;
use Nette\SmartObject;
use Nette\Utils\ArrayHash;


class ReviewFormFactory
{
    use SmartObject;

    /** @var FormFactory Továrnička pro společné formuláře. */
    private FormFactory $formFactory;

    /** @var User Model pro přístup k atuálnímu uživateli. */
    private User $user;

    /** @var ReviewManager */
    private ReviewManager $reviewManager;

    /** @var GalleryManager */
    private GalleryManager $galleryManager;


    /**
     * Konstruktor GalleryFormFactory.
     * @param FormFactory $formFactory Záklaní továrnička pro formuláře.
     * @param User $user Model aktuálně přihlášeného uživatele.
     * @param ReviewManager $reviewManager Model pro práci s recenzemi.
     * @param GalleryManager $galleryManager Model pro práci s produkty.
     */
    public function __construct(
        FormFactory $formFactory,
        User $user,
        ReviewManager $reviewManager,
        GalleryManager $galleryManager,
    ) {
        $this->formFactory = $formFactory;
        $this->user = $user;
        $this->reviewManager = $reviewManager;
        $this->galleryManager = $galleryManager;
    }

    /**
     * Vytváří a vrací komponentu formuláře pro správu produktu.
     *
     * @param callable $onSuccess specifická funkce, která se vykoná po úspěšném odeslání formuláře.
     * @param callable $userNotLogIn funkce, která se vyokná, není-li uživatel přihlášený.
     * @param callable $duplicateReview specifická funkce, která se vykoná, pokud uživatel produkt již ohodnotil.
     *
     * @return Form Formulář s předpřipravenými vlastnostmi pro produkt.
     */
    public function create($onSuccess, $userNotLogIn, $duplicateReview)
    {
        $form = $this->formFactory->create();
        $form->addHidden('galerie_id');// taky review a uprava parametru aby bylo označení ve shodě!
        $form->addTextArea('content', 'Jak jste spokojení?')->setRequired();//content
        $form->addSubmit('send', 'Odeslat');
        $form->onSuccess[] = function ($form, $values) use ($onSuccess, $userNotLogIn, $duplicateReview) {
            $this->reviewFormSucceeded($form, $values, $onSuccess, $userNotLogIn, $duplicateReview);
        };
        return $form;
    }

    /**
     * Funkce se vykonaná při úspěšném odeslání formuláře pro správu produktu.
     * @param Form $form Formulář pro správu produktů
     * @param ArrayHash $values Hodnoty formuláře
     * @param callable $onSuccess specifická funkce, která se vykoná po úspěšném odeslání formuláře.
     * @param callable $userNotLogIn funkce, která se vyokná, není-li uživatel přihlášený.
     * @param callable $duplicateReview specifická funkce, která se vykoná, pokud uživatel produkt již ohodnotil.
     */
    public function reviewFormSucceeded($form, $values, $onSuccess, $userNotLogIn, $duplicateReview)
    {
        if (!$this->user->isLoggedIn()) {
            $userNotLogIn($form, $values);
        }
        // K recenzi doplníme ID aktuálně přihlášeného uživatele.
        $values['user_id'] = $this->user->getId();
        try { // Pokusíme se přidat recenzi.
            $this->reviewManager->addReview($values);
            $onSuccess($form, $values);
        } catch (UniqueConstraintViolationException $ex) {
            $duplicateReview($form, $values);
        }
    }
}