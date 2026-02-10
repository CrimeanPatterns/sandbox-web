<?php
/**
 * DBC API clients
 */
require_once(__DIR__ . "/../3dParty/deathbycaptcha/deathbycaptcha.php");

class DeathByCaptchaRecognizer {

    public $RecognizeTimeout = 60;

    public $username;
    public $password;

    public $OnMessage;

    public function recognizeFile($file) {
        $result = null;
        $client = new DeathByCaptcha_SocketClient($this->username, $this->password);
        $client->is_verbose = true;
        $startTime = time();
        for ($i = 0; $i < 3; $i++) {
            // Put your CAPTCHA image file name, file resource, or vector of bytes,
            // and optional solving timeout (in seconds) here; you'll get CAPTCHA
            // details array on success.
            if ($captcha = $client->decode($file, DeathByCaptcha_Client::DEFAULT_TIMEOUT)) {
                $result = $captcha;
                $this->log("[Step #{$i}]: CAPTCHA {$captcha['captcha']} solved: {$captcha['text']}, duration: ".(time() - $startTime));

                return $result;
            }// if ($captcha = $client->decode($file, DeathByCaptcha_Client::DEFAULT_TIMEOUT))
        }// for ($i = 0; $i < 6; $i++)

        return $result;
    }

    public function reportIncorrectlySolvedCAPTCHA($captchaID) {
        $client = new DeathByCaptcha_SocketClient($this->username, $this->password);
        $client->is_verbose = true;
        // Report an incorrectly solved CAPTCHA.
        // Make sure the CAPTCHA was in fact incorrectly solved!
        $client->report($captchaID);
        $this->log("Incorrectly solved CAPTCHA ($captchaID) has been reported");
    }

    public function getBalance() {
        $client = new DeathByCaptcha_SocketClient($this->username, $this->password);
        $client->is_verbose = true;
        return $client->get_balance();
    }

    public function getUser() {
        $client = new DeathByCaptcha_SocketClient($this->username, $this->password);
        $client->is_verbose = true;
        return $client->get_user();
    }

    protected function log($message) {
        if(isset($this->OnMessage))
            call_user_func($this->OnMessage, $message);
    }

}
