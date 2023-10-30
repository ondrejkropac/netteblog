<?php

declare(strict_types=1);

namespace App\Model;

use Nette;
use Nette\Security\Passwords;
use Nette\Database\Explorer;


/**
 * Users management.
 */
final class UserManager implements Nette\Security\Authenticator
{
    use Nette\SmartObject;

    /** Konstanty pro práci s databází. */
    private const
        TABLE_NAME = 'user',
        COLUMN_ID = 'user_id',
        COLUMN_NAME = 'username',
        COLUMN_PASSWORD_HASH = 'password',
        COLUMN_EMAIL = 'email',
        COLUMN_ROLE = 'role';


    /** @var Explorer */
    private Explorer $database;

    /** @var Passwords */
    private Passwords $passwords;


    public function __construct(Explorer $database, Passwords $passwords)
    {
        $this->database = $database;
        $this->passwords = $passwords;
    }

    public function userName($id) {
        return $this->database->table(self::TABLE_NAME)->select(self::COLUMN_NAME)->where(self::COLUMN_ID, $id)->fetch();
    }


    /**
     * Přihlásí uživatele do systému.
     * @param array $credentials přihlašovací údaje uživatele (jméno a heslo)
     * @return Nette\Security\IIdentity Vrací identitu přihlášeného uživatele pro další manipulaci
     * @throws Nette\Security\AuthenticationException Jestliže došlo k chybě při přihlášení, např. špatné heslo nebo uživatelské jméno.
     */
    public function authenticate(string $username, string $password): Nette\Security\IIdentity
    {
        // Najde a vrátí první záznam uživatele s daným jménem v databázi nebo false, pokud takový uživatel neexistuje.
        $row = $this->database->table(self::TABLE_NAME)
            ->where(self::COLUMN_NAME, $username)
            ->fetch();

        // Ověření uživatele.
        if (!$row) { // Vyhodí výjimku, pokud uživatel/záznam neexistuje.
            throw new Nette\Security\AuthenticationException('Zadané uživatelské jméno neexistuje.', self::IDENTITY_NOT_FOUND);
            // Vyhodí výjimku, pokud je heslo špatně.
        } elseif (!$this->passwords->verify($password, $row[self::COLUMN_PASSWORD_HASH])) {  // Ověří zadané heslo.
            throw new Nette\Security\AuthenticationException('Zadané heslo není správně.', self::INVALID_CREDENTIAL);

        } elseif ($this->passwords->needsRehash($row[self::COLUMN_PASSWORD_HASH])) { // Zjistí zda heslo potřebuje rehashovat.
            // Rehashuje heslo (bezpečnostní opatření).
            $row->update([
                self::COLUMN_PASSWORD_HASH => $this->passwords->hash($password),
            ]);
        }

        // Příprava atributů z databáze pro identitu úspěšně přihlášeného uživatele.
        $arr = $row->toArray(); // Převede uživatelská data z databáze na PHP pole.
        unset($arr[self::COLUMN_PASSWORD_HASH]); // Odstraní hash hesla z uživatelských dat (kvůli bezpečnosti).

        // Vrátí novou identitu úspěšně přihlášeného uživatele.
        return new Nette\Security\SimpleIdentity($row[self::COLUMN_ID], $row[self::COLUMN_ROLE], $arr);
    }

    /**
     * Registruje/přidá nového uživatele do systému.
     * @param string $username uživatelské jméno
     * @param string $email email
     * @param string $password heslo
     * @throws DuplicateNameException|Nette\Utils\AssertionException Jestliže uživatel s daným jménem již existuje.
     */
    public function add(string $username, string $email, string $password): void
    {
        Nette\Utils\Validators::assert($email, 'email');
        try {
            // Pokusí se vložit nového uživatele do databáze.
            $this->database->table(self::TABLE_NAME)->insert([
                self::COLUMN_NAME => $username,
                self::COLUMN_PASSWORD_HASH => $this->passwords->hash($password),
                self::COLUMN_EMAIL => $email,
            ]);
        } catch (Nette\Database\UniqueConstraintViolationException $e) {
            // Vyhodí výjimku, pokud uživatel s daným jménem již existuje.
            throw new DuplicateNameException;
        }
    }
}


/**
 * Výjimka pro duplicitní uživatelské jméno.
 * @package App\Model
 */
class DuplicateNameException extends \Exception
{
    // Nastavení výchozí chybové zprávy.
    protected $message = 'Uživatel s tímto jménem je již zaregistrovaný.';
}

    /**
     * Předá jmeno uživatele.
     * @param IRow $person_detail detailní informace o osobě
     * @return string název osoby
     */
    /*
    ALE ONO TO NENÍ TAK SNADNÝ PROTOŽE V REVIEW USER NAME NENÍ ULOŽENO...
    public static function getName(IRow $user_id)
    {
        $name = '';
        if (isset($user_id[self::COLUMN_NAME]))
            $name = $user_id[self::COLUMN_NAME];
        return $name;
    }*/