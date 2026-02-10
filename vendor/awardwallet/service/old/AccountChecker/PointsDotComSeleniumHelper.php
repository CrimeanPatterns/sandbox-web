<?php

trait PointsDotComSeleniumHelper {

	use \SeleniumCheckerHelper;

	protected $waitTimeout = 5;

	protected $loadTimeout = 20;

	protected $externalStyles = null;

	protected function fillTextInputs(array $data) {
		foreach($data as $name => $value) {
			$input = $this->waitForElement(\WebDriverBy::xpath(sprintf('//div[@id="%s"]/input[@type="text" or @type="password"]', $name)), $this->waitTimeout);
			if (!$input)
				return $this->fail(sprintf('text input %s not found', $name));
			$input->clear();
			// assumed div.firstChild for target input will work every time
			$this->driver->executeScript(sprintf('document.getElementById(\'%s\').firstChild.dispatchEvent(new FocusEvent(\'focus\', {view: window, cancelable: true}));', $name));
			$input->sendKeys($value);
			$this->driver->executeScript(sprintf('document.getElementById(\'%s\').firstChild.dispatchEvent(new FocusEvent(\'blur\', {view: window, cancelable: true}));', $name));
			$this->http->Log(sprintf('text input %s filled', $name));
		}
		return true;
	}

	protected function fillSelectInputs($data) {
		foreach($data as $name => $value) {
			$arrow = $this->waitForElement(\WebDriverBy::xpath(sprintf('//div[@id="%s"]/img', $name)), $this->waitTimeout);
			if (!$arrow)
				return $this->fail(sprintf('arrow for select %s not found', $name));
			$arrow->click();
			$option = $this->waitForElement(\WebDriverBy::xpath(sprintf('//div[@id="%s_list"]/div[text()="%s"]', $name, $value)), $this->waitTimeout);
			if (!$option)
				return $this->fail(sprintf('option %s for select %s not found', $value, $name));
			$option->click();
			$this->http->Log(sprintf('option %s for select %s selected', $value, $name));
		}
		return true;
	}

	protected function accountInfoSubmit() {
		$button = $this->waitForElement(\WebDriverBy::id('mv_submit'), $this->waitTimeout);
		if (!$button)
			return $this->fail('continue button on account info form not found');
		$button->click();
		$test = $this->waitForElement(\WebDriverBy::id('pay_card_type'), $this->loadTimeout);
		if (!$test)
			return $this->fail('cc form not found or empty');
		return true;
	}

	protected function ccSubmitAndConfirm() {
		$button = $this->waitForElement(\WebDriverBy::id('pay_submit'), $this->waitTimeout);
		if (!$button)
			return $this->fail('continue button not found');
		$button->click();

		$check = $this->waitForElement(\WebDriverBy::xpath('//div[@id="review_accept"]/input[@type="checkbox"]'), $this->loadTimeout);
		if (!$check)
			return $this->fail('no confirm form');
		$check->click();
		$button = $this->waitForElement(\WebDriverBy::id('review_submit'), $this->waitTimeout);
		if (!$button)
			return $this->fail('no confirm submit button');
		$button->click();
		return true;
	}

	protected function fail($message = null) {
		if (isset($message))
			$this->http->Log($message, LOG_LEVEL_ERROR);
		$this->saveFrameContent();
		return false;
	}

	protected function saveFrameContent() {
		$this->http->SetBody($this->driver->executeScript('return document.documentElement.innerHTML'));
		if (isset($this->externalStyles))
			$this->http->Response['body'] = str_replace('</head>', '<style>'.$this->externalStyles.'</style></head>', $this->http->Response['body']);
		foreach($this->http->FindNodes('//link[@rel="stylesheet"]/@href') as $href) {
			if (strpos($href, 'http') !== 0 && strpos($href, 'nonCacheableAssetDownload') === false)
				$this->http->Response['body'] = str_replace($href, 'https://buy.points.com/PointsPartner/' . $href, $this->http->Response['body']);
		}
		$this->http->SaveResponse();
	}

	protected function loadStyles() {
		$links = $this->driver->findElements(\WebDriverBy::xpath('//link[@rel="stylesheet"]'));
		$script = <<<_s
if (!window.saveCss) {
	window.totalStyles = '';
	window.saveCss = function() {
		totalStyles += this.contentDocument.body.textContent;
		this.remove();
	};
}
_s;
		$this->driver->executeScript($script);
		foreach($links as $link) {
			$href = $link->getAttribute('href');
			if (strpos($href, 'nonCacheableAssetDownload') === false)
				continue;
			$script = <<<_script
var frame = document.createElement('iframe');
frame.src = '%s';
frame.classList.add('cssFrame');
frame.onload = saveCss;
document.body.appendChild(frame);
_script;
			$this->driver->executeScript(sprintf($script, $href));
		}
		for($i=0;$i<5;$i++) {
			$frames = $this->driver->findElements(\WebDriverBy::className('cssFrame'));
			if (count($frames) === 0) {
				$this->http->Log('styles loaded');
				$this->externalStyles = $this->driver->executeScript('return totalStyles;');
				return;
			}
			sleep(1);
		}
		$this->http->Log('styles failed to load', LOG_LEVEL_ERROR);
	}

}