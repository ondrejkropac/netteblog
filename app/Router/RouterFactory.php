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

namespace App\Router;

use Nette;
use Nette\Application\Routers\Route;
use Nette\Application\Routers\RouteList;

/**
 * Továrna na routovací pravidla.
 * Řídí směrování a generovaní URL adres v celé aplikaci.
 * @package App
 */
final class RouterFactory
{
    use Nette\StaticClass;

    /**
     * Vytváří a vrací seznam routovacích pravidel pro aplikaci.
     * @return RouteList výsledný router pro aplikaci
     */
    public static function createRouter(): RouteList
    {
        $router = new RouteList;
        $router->addRoute('kontakt', 'Core:Contact:default');
        $router->addRoute('administrace', 'Core:Administration:default');

        $router->addRoute('<action>', [
            'presenter' => 'Core:Administration',
            'action' => [
                Route::FILTER_STRICT => true,
                Route::FILTER_TABLE => [
                    // řetězec v URL => akce presenteru
                    'administrace' => 'default',
                    'prihlaseni' => 'login',
                    'odhlasit' => 'logout',
                    'registrace' => 'register'
                ]
            ]
        ]);

        /*$router->addRoute('<action>[/<url>]', [
            'presenter' => 'Core:Article',
            'action' => [
                Route::FILTER_STRICT => true,
                Route::FILTER_TABLE => [
                    // řetězec v URL => akce presenteru
                    'seznam-clanku' => 'list',
                    'editor' => 'editor',
                    'odstranit' => 'remove'
                ]
            ]
        ]);*/

        $router->addRoute('<action>[/<url>]', [
            'presenter' => 'Core:Clanek',
            'action' => [
                Route::FILTER_STRICT => true,
                Route::FILTER_TABLE => [
                    // řetězec v URL => akce presenteru
                    //'clanky' => 'default',
                    'clanky' => 'list',
                    'editor' => 'editor',
                    //'redakceedit' => 'redactionEditor',
                    //'editvydani' => 'publishEditor',
                    //'smaz_plan' => 'removeRedaction',
                    'last' => 'last',
                    'schvalene' => 'schvalene',
                    'odstranit' => 'remove'
                ]
            ]
        ]);

        $router->addRoute('<action>[/<url>]', [
            'presenter' => 'Core:Redaction',
            'action' => [
                Route::FILTER_STRICT => true,
                Route::FILTER_TABLE => [
                    // řetězec v URL => akce presenteru
                    'redakceedit' => 'redactionEditor',
                    'editvydani' => 'publishEditor',
                    'smaz_plan' => 'removeRedaction'
                ]
            ]
        ]);
        $router->addRoute('bloggalerie', 'Core:BlogGalerie:list');

        $router->addRoute('bloggalerie/<action>[/<url>]', [
            'presenter' => 'Core:BlogGalerie',
            'action' => [
                Route::FILTER_STRICT => true,
                Route::FILTER_TABLE => [
                    // řetězec v URL => akce presenteru
                    //'odkazy' => 'list', // výchozí routa... $router->addRoute('bloggalerie', 'Core:BlogGalerie:list');
                    'seznam-galerii' => 'default',
                    'manage' => 'manage',
                    'odstranit' => 'remove'
                ]
            ]
        ]);

        $router->addRoute('gallery', 'Core:Gallery:list');

        $router->addRoute('gallery/<action>[/<url>]', [
            'presenter' => 'Core:Gallery',
            'action' => [
                Route::FILTER_STRICT => true,
                Route::FILTER_TABLE => [
                    // řetězec v URL => akce presenteru
                    //'odkazy' => 'list',
                    'seznam' => 'default',
                    'manage' => 'manage',
                    'odstranit' => 'remove'
                ]
            ]
        ]);

        $router->addRoute('profile', 'Core:Profile:list');

        $router->addRoute('profile/<action>[/<url>]', [
            'presenter' => 'Core:Profile',
            'action' => [
                Route::FILTER_STRICT => true,
                Route::FILTER_TABLE => [
                    // řetězec v URL => akce presenteru
                    'uzivatele' => 'default',
                    'editor' => 'editor',
                    'odstranit' => 'remove'
                ]
            ]
        ]);

        $router->addRoute('online', 'Core:Online:list');

        $router->addRoute('online/<action>[/<url>]', [
            'presenter' => 'Core:Online',
            'action' => [
                Route::FILTER_STRICT => true,
                Route::FILTER_TABLE => [
                    // řetězec v URL => akce presenteru
                    'seznam-odkazu' => 'default',
                    'editor' => 'editor',
                    'odstranit' => 'remove'
                ]
            ]
        ]);

        $router->addRoute('zapisnik', 'Core:Zapisnik:default');

        $router->addRoute('zapisnik/<action>[/<url>]', [
            'presenter' => 'Core:Zapisnik',
            'action' => [
                Route::FILTER_STRICT => true,
                Route::FILTER_TABLE => [
                    // řetězec v URL => akce presenteru
                    //'seznam-poznamek' => 'default',
                    'poznamky' => 'list',
                    'editor' => 'editor',
                    'odstranit' => 'remove'
                ]
            ]
        ]);

        $router->addRoute('tasker', 'Core:Tasker:default');

        $router->addRoute('tasker/<action>[/<url>]', [
            'presenter' => 'Core:Tasker',
            'action' => [
                Route::FILTER_STRICT => true,
                Route::FILTER_TABLE => [
                    // řetězec v URL => akce presenteru
                    //'seznam-poznamek' => 'default',
                    'clanky' => 'list',
                    'editor' => 'editor',
                    'odstranit' => 'remove'
                ]
            ]
        ]);

        $router->addRoute('todo', 'Core:Todo:default');

        $router->addRoute('todo/<action>[/<url>]', [
            'presenter' => 'Core:Todo',
            'action' => [
                Route::FILTER_STRICT => true,
                Route::FILTER_TABLE => [
                    // řetězec v URL => akce presenteru
                    //'seznam-prace' => 'default',
                    'tabulka' => 'list',
                    'editor' => 'editor',
                    'odstranit' => 'remove'
                ]
            ]
        ]);

        $router->addRoute('udalosti', 'Core:Event:default');

        $router->addRoute('event/<action>[/<url>]', [
            'presenter' => 'Core:Event',
            'action' => [
                Route::FILTER_STRICT => true,
                Route::FILTER_TABLE => [
                    // řetězec v URL => akce presenteru
                    //'events' => 'default',
                    'editor' => 'editor',
                    'odstranit' => 'remove'
                ]
            ]
        ]);

        $router->addRoute('hlasovani', 'Core:Poll:default');

        $router->addRoute('poll/<action>[/<url>]', [
            'presenter' => 'Core:Poll',
            'action' => [
                Route::FILTER_STRICT => true,
                Route::FILTER_TABLE => [
                    // řetězec v URL => akce presenteru
                    //'polls' => 'default',
                    'editor' => 'editor',
                    'odstranit' => 'remove'
                ]
            ]
        ]);

        $router->addRoute('riders', 'Core:Riders:default');
        $router->addRoute('riders/<id>', 'Core:Riders:detail');

        $router->addRoute('list', 'Core:List:default');

        $router->addRoute('creator', 'Core:Creator:default');

        $router->addRoute('komentare', 'Core:Comment:list');

        //$router->addRoute('[<url>]', 'Core:Article:default');

        //$router->addRoute('[<url>]', 'Core:Clanek:default_art'); //- je potřeba oddělit úvodní článek do jiné routy protože [<url>] je rezervovaná kategoriím! a potom to koliduje
        $router->addRoute('clanek/<id>', 'Core:Clanek:detail');
        $router->addRoute('clanek/<url>', 'Core:Clanek:detail');
        $router->addRoute('bloggalerie/<id>', 'Core:BlogGalerie:detail');
        $router->addRoute('bloggalerie/grid/<id>', 'Core:BlogGalerie:grid');
        $router->addRoute('gallery/<id>', 'Core:Gallery:detail');
        $router->addRoute('profile/<id>', 'Core:Profile:detail');
        $router->addRoute('online/<id>', 'Core:Online:detail');
        $router->addRoute('zapisnik/<id>', 'Core:Zapisnik:detail');
        $router->addRoute('tasker/<id>', 'Core:Tasker:detail');
        $router->addRoute('todo/<id>', 'Core:Todo:detail');
        $router->addRoute('event/<id>', 'Core:Event:detail');
        $router->addRoute('poll/<id>', 'Core:Poll:detail');
        $router->addRoute('[<url>]', 'Core:Clanek:default');
        //$router->addRoute('<url>', 'Core:Clanek:default'); !! NEFUNGVALO

        return $router;
    }
}