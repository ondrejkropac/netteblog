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

use Nette;
use Nette\Application\UI\Form;
use Nette\Security\User;

/**
 * Továrna na přihlašovací formulář.
 * @package App\Forms
 */
final class SignInFormFactory
{
    use Nette\SmartObject;

    /** @var FormFactory Továrna na formuláře. */
    private FormFactory $factory;

    /** @var User Uživatel. */
    private User $user;

    /**
     * Konstruktor s injektovanou továrnou na formuláře a uživatelem.
     * @param FormFactory $factory automaticky injektovaná továrna na formuláře
     * @param User        $user    automaticky injektovaný uživatel
     */
    public function __construct(FormFactory $factory, User $user)
    {
        $this->factory = $factory;
        $this->user = $user;
    }

    /**
     * Vytváří a vrací přihlašovací formulář.
     * @param callable $onSuccess specifická funkce, která se vykoná po úspěšném odeslání formuláře
     * @return Form přihlašovací formulář
     */
    public function create(callable $onSuccess): Form
    {
        $form = $this->factory->create();

        $form->addText('username', 'Jméno:')
            ->setRequired('Zadej prosím své uživatelské jméno.');

        $form->addPassword('password', 'Heslo:')
            ->setRequired('Zadej prosím své heslo.');

        $form->addCheckbox('remember', 'Zapamatovat si mě');

        $form->addSubmit('send', 'Sign in');

        $form->onSuccess[] = function (Form $form, \stdClass $values) use ($onSuccess): void {
            try {
                // Zkusíme se přihlásit.
                $this->user->setExpiration($values->remember ? '14 days' : '20 minutes');
                $this->user->login($values->username, $values->password);
            } catch (Nette\Security\AuthenticationException $e) {
                // Přidáme chybovou zprávu alespoň do formuláře.
                $form->addError('Zadané přihlašovací jméno nebo heslo není správně.');
                return;
            }
            $onSuccess();
        };

        return $form;
    }
}