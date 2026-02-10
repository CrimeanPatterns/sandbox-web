<?php

namespace AwardWallet\Common\Selenium;

use Facebook\WebDriver\Remote\RemoteWebDriver;
use Facebook\WebDriver\WebDriverBy;

class OldWebDriverTranslator
{

    /**
     * @var DebugWebDriver
     */
    private $delegate;

    public function __construct(RemoteWebDriver $delegate)
    {
        $this->delegate = $delegate;
    }

    /**
     * Get current selenium sessionID
     *
     * @return string
     */
    public function getSessionID()
    {
        return $this->delegate->getSessionID();
    }

    /**
     * Set the command executor of this RemoteWebdriver
     *
     * @return \HttpCommandExecutor
     */
    public function getCommandExecutor()
    {
        return $this->delegate->getCommandExecutor();
    }

    /**
     * An abstraction for managing stuff you would do in a browser menu. For
     * example, adding and deleting cookies.
     *
     * @return \WebDriverOptions
     */
    public function manage()
    {
        return $this->delegate->manage();
    }

    /**
     * Get a string representing the current URL that the browser is looking at.
     *
     * @return string The current URL.
     */
    public function getCurrentURL()
    {
        return $this->delegate->getCurrentURL();
    }

    /**
     * Load a new web page in the current browser window.
     *
     * @param string $url
     *
     * @return RemoteWebDriver The current instance.
     */
    public function get($url)
    {
        $this->delegate->get($url);

        return $this;
    }

    /**
     * Find the first WebDriverElement using the given mechanism.
     *
     * @param WebDriverBy $by
     * @return \RemoteWebElement NoSuchElementException is thrown in
     *    HttpCommandExecutor if no element is found.
     * @see WebDriverBy
     */
    public function findElement(\WebDriverBy $by)
    {
        return $this->delegate->findElement($this->convertBy($by));
    }

    /**
     * Inject a snippet of JavaScript into the page for execution in the context
     * of the currently selected frame. The executed script is assumed to be
     * synchronous and the result of evaluating the script will be returned.
     *
     * @param string $script The script to inject.
     * @param array $arguments The arguments of the script.
     * @return mixed The return value of the script.
     */
    public function executeScript($script, array $arguments = array())
    {
        return $this->delegate->executeScript($script, $arguments);
    }

    /**
     * Quits this driver, closing every associated window.
     *
     * @return void
     */
    public function quit()
    {
        return $this->delegate->quit();
    }

    /**
     * @return \RemoteMouse
     */
    public function getMouse()
    {
        return $this->delegate->getMouse();
    }

    /**
     * Find all WebDriverElements within the current page using the given mechanism.
     *
     * @param \WebDriverBy $by
     * @return \RemoteWebElement[] A list of all WebDriverElements, or an empty array if nothing matches
     */
    public function findElements($by)
    {
        return $this->delegate->findElements($this->convertBy($by));
    }

    private function convertBy(\WebDriverBy $by)
    {
        switch ($by->getMechanism()) {
            case 'class name':
                return WebDriverBy::className($by->getValue());
            case 'id':
                return WebDriverBy::id($by->getValue());
            case 'name':
                return WebDriverBy::name($by->getValue());
            case 'xpath':
                return WebDriverBy::xpath($by->getValue());
            case 'tag name':
                return WebDriverBy::tagName($by->getValue());
            case 'css selector':
                return WebDriverBy::cssSelector($by->getValue());
            case 'link text':
                return WebDriverBy::linkText($by->getValue());
            case 'partial link text':
                return WebDriverBy::partialLinkText($by->getValue());
            default:
                throw new \Exception("Unknown mechanism: {$by->getMechanism()}");
        }
    }

    /**
     * Get the source of the last loaded page.
     *
     * @return string The current page source.
     */
    public function getPageSource()
    {
        return $this->delegate->getPageSource();
    }

    /**
     * Switch to a different window or frame.
     *
     * @return \RemoteTargetLocator
     * @see \RemoteTargetLocator
     */
    public function switchTo()
    {
        return $this->delegate->switchTo();
    }

    /**
     * Inject a snippet of JavaScript into the page for asynchronous execution in the context of the currently selected
     * frame.
     *
     * The driver will pass a callback as the last argument to the snippet, and block until the callback is invoked.
     *
     * You may need to define script timeout using `setScriptTimeout()` method of `WebDriverTimeouts` first.
     *
     * @param string $script The script to inject.
     * @param array $arguments The arguments of the script.
     * @return mixed The value passed by the script to the callback.
     */
    public function executeAsyncScript($script, array $arguments = [])
    {
        return $this->delegate->executeAsyncScript($script, $arguments);
    }

    public function takeScreenshot($save_as = null)
    {
        return $this->delegate->takeScreenshot($save_as);
    }

    public function close()
    {
        $this->delegate->close();

        return $this;
    }

    public function getServerInfo() : ServerInfo
    {
        return $this->delegate->getServerInfo();
    }

    public function setServerInfo(ServerInfo $serverInfo)
    {
        $this->delegate->setServerInfo($serverInfo);
    }

}
