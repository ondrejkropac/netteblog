<?php

declare(strict_types=1);

namespace App\Presenters;

use Nette;
use Nette\Application\Responses;
use Nette\Http;
use Tracy\ILogger;

/**
 * Presenter pro vlastní zpracování chyb na stránce.
 * @package App\Presenters
 */
final class ErrorPresenter implements Nette\Application\IPresenter
{
    use Nette\SmartObject;

    /** @var ILogger Služba pro logování. */
    private $logger;

    /**
     * Konstruktor s injektovanou službou pro logování.
     * @param ILogger $logger automaticky injektovaná Nette služba pro logování
     */
    public function __construct(ILogger $logger)
    {
        $this->logger = $logger;
    }


    /**
     * Zpracovává vyhozenou výjimku vygenerováním vlastní odpovědi.
     * @param Nette\Application\Request $request originální požadavek, který způsobil výjimku
     * @return Nette\Application\IResponse odpověď na vyhozenou výjimku
     */
    public function run(Nette\Application\Request $request): Nette\Application\IResponse
    {
        // Získání instance výjimky.
        $e = $request->getParameter('exception');

        // Pokud jde o chybu v dotazu, vrať jako odpověď přesměrování na vlastní chybovou stránku.
        if ($e instanceof Nette\Application\BadRequestException) {
            // $this->logger->log("HTTP code {$e->getCode()}: {$e->getMessage()} in {$e->getFile()}:{$e->getLine()}", 'access');
            [$module, , $sep] = Nette\Application\Helpers::splitName($request->getPresenterName());
            $errorPresenter = $module . $sep . 'Error4xx';
            return new Responses\ForwardResponse($request->setPresenterName($errorPresenter));
        }

        // Jinak se jedná o chybu serveru.
        $this->logger->log($e, ILogger::EXCEPTION);

        // Vrací jako odpověď chybovou stránku serveru.
        return new Responses\CallbackResponse(function (Http\IRequest $httpRequest, Http\IResponse $httpResponse): void {

            // Pokud je jako odpověď očekáváno HTML, načti šablonu pro chybovou stránku serveru.
            if (preg_match('#^text/html(?:;|$)#', (string) $httpResponse->getHeader('Content-Type'))) {
                require __DIR__ . '/../templates/Error/500.phtml';
            }
        });
    }
}