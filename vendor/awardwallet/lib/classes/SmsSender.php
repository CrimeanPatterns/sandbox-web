<?
/**
 * this class will send SMS through gate smsmail.com
 * sms send rate limited to 1 sms in 10 minutes to avoid spending cash to quick
 * smsmail.com authorizes sender by sender email, so
 * EMAIL_FROM constant should match smsmail.com account
 *
 * define recipients emails in secure/sitename.php, constant SMS_RECIPIENTS
 * format is:
 * 		define('SMS_RECIPIENTS', 'secretcode1@smsmail.com|subject1,secretcode2@smsmail.com|subject2');
 */
class SmsSender{

	/**
	 * how many seconds to wait between sms. call to ->send in silence period will be ignored
	 * @var int
	 */
	public $silencePeriod = 600;
	/**
	 * @var array
	 * example:
	 * 		array('email1@smsmail.com' => 'subject1', 'email2@smsmail.com' => 'subject2'))
	 */
	public $recipients = array();

	public function __construct(){
		$this->recipients = array();
		if(defined('SMS_RECIPIENTS'))
		foreach(explode(',', SMS_RECIPIENTS) as $recipient){
			$pair = explode("|", $recipient);
			$this->recipients[$pair[0]] = $pair[1];
		}
	}

	public function send($text){
		if(count($this->recipients) == 0)
			DieTrace("no recipients");
		if(DBUtils::createExpirableParam('SMSSentDate', $this->silencePeriod)){
			foreach($this->recipients as $email => $subject)
				mailTo($email, $subject, $text, EMAIL_HEADERS);
		}
	}

}