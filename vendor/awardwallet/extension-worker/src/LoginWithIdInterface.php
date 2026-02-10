<?php

namespace AwardWallet\ExtensionWorker;

interface LoginWithIdInterface
{

    /**
     * это самый первый метод который будет вызван
     * @return string - на каком url будет открыта новая вкладка для начала проверки / автологина
     */
    public function getStartingUrl(AccountOptions $options): string;

    /**
     * будет вызван после загрузки вкладки по адресу который вернул getStartingUrl
     * @return bool - залогинен ли пользователь
     */
    public function isLoggedIn(Tab $tab): bool;

    /**
     * Если isLoggedIn вернул true, то будет вызван этот метод, чтобы определить идентификатор залогиненного пользователя
     * @return string - идентификатор залогиненного пользователя. обычно это Account Number, реже Email, Login
     */
    public function getLoginId(Tab $tab): string;

    /**
     * если залогиненный на сайте пользователь не совпадает с желаемым - будет вызван этот метод
     * разлогинить пользователя
     */
    public function logout(Tab $tab): void;

    /**
     * Авторизует пользователя на сайте. Заполняет форму логина, нажимает "Войти" и т.д.
     */
    public function login(Tab $tab, Credentials $credentials): LoginResult;

}