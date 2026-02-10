<?php

class MouseMover
{

	/**
	 * @var SeleniumDebugWebDriver
	 */
	private $driver;

	/**
	 * mouse position x
	 */
	private $coords = ['x' => 0, 'y' => 0];

	/**
	 * @var \Psr\Log\LoggerInterface
	 */
	public $logger;

	/**
	 * mouse movement duration, microseconds
	 * @var int
	 */
	public $duration = 300000;

	/**
	 * how many move events will be generated during move
	 * @var int
	 */
	public $steps = 50;

	private $cursorEnabled = false;
    /**
     * @var RemoteMouse
     */
	private $mouse;

    /**
     * @param SeleniumDebugWebDriver $driver
     */
	public function __construct($driver){
		$this->driver = $driver;
		$this->logger = new \Psr\Log\NullLogger();
        // set starting coordinates in top left third in case coordinates calculated from center of the body
        $rootSize = $this->getRootElement()->getSize();
        $this->coords = [
            'x' => min(400, random_int(20, max(100, floor($rootSize->getWidth() / 3)))),
            'y' => min(200, random_int(10, max(100, floor($rootSize->getHeight() / 3))))
        ];
        $this->mouse = $this->driver->getMouse();
	}

	public function enableCursor()
    {
        $this->driver->executeScript("
            const mouse = document.createElement('img');
            mouse.setAttribute(
              'src',
              'data:image/png;base64,' +
                'iVBORw0KGgoAAAANSUhEUgAAABQAAAAeCAQAAACGG/bgAAAAAmJLR0QA/4ePzL8AAAAJcEhZcwAA' +
                'HsYAAB7GAZEt8iwAAAAHdElNRQfgAwgMIwdxU/i7AAABZklEQVQ4y43TsU4UURSH8W+XmYwkS2I0' +
                '9CRKpKGhsvIJjG9giQmliHFZlkUIGnEF7KTiCagpsYHWhoTQaiUUxLixYZb5KAAZZhbunu7O/PKf' +
                'e+fcA+/pqwb4DuximEqXhT4iI8dMpBWEsWsuGYdpZFttiLSSgTvhZ1W/SvfO1CvYdV1kPghV68a3' +
                '0zzUWZH5pBqEui7dnqlFmLoq0gxC1XfGZdoLal2kea8ahLoqKXNAJQBT2yJzwUTVt0bS6ANqy1ga' +
                'VCEq/oVTtjji4hQVhhnlYBH4WIJV9vlkXLm+10R8oJb79Jl1j9UdazJRGpkrmNkSF9SOz2T71s7M' +
                'SIfD2lmmfjGSRz3hK8l4w1P+bah/HJLN0sys2JSMZQB+jKo6KSc8vLlLn5ikzF4268Wg2+pPOWW6' +
                'ONcpr3PrXy9VfS473M/D7H+TLmrqsXtOGctvxvMv2oVNP+Av0uHbzbxyJaywyUjx8TlnPY2YxqkD' +
                'dAAAAABJRU5ErkJggg=='
            );
            mouse.setAttribute('id', 'selenium-mouse');
            mouse.setAttribute(
              'style',
              'position: absolute; z-index: 999999; pointer-events: none; left:0; top:0'
            );
            document.body.appendChild(mouse);
            document.onmousemove = function(e) {
              document.getElementById('selenium-mouse').style.left = e.pageX + 'px';
              document.getElementById('selenium-mouse').style.top = e.pageY + 'px';
            };
        ");
    }


    public function moveToCoordinates($target, $random = ['x' => 20, 'y' => 5])
    {
        $steps = round($this->steps * rand(80, 120) / 100);
        foreach (['x', 'y'] as $axis) {
            $target[$axis]  += rand(0, $random[$axis]);
            $target['distance-' . $axis] = abs($this->coords[$axis] - $target[$axis]);
            $target['step-' . $axis] = round($target['distance-'. $axis] / $steps, 2);
        }
        $this->logger->debug("moving mouse from {$this->coords['x']},{$this->coords['y']} to {$target['x']},{$target['y']}, distance: {$target['distance-x']},{$target['distance-y']}, steps: $steps, step: {$target['step-x']},{$target['step-y']}");

//        if (!$this->cursorEnabled) {
//            $this->enableCursor();
//            $this->cursorEnabled = true;
//        }

        $pause = $this->duration / $steps;
        $rootCoords = $this->getRootElement()->getCoordinates();
        $correction = $this->detectCorrection();

        while (abs($this->coords['x'] - $target['x']) > 0.3 || abs($this->coords['y'] - $target['y']) > 0.3) {
            foreach (['x', 'y'] as $axis) {
                $stepDistance = $target['step-' . $axis] * (rand(80, 120) / 100);
                $distance = $target[$axis] - $this->coords[$axis];
                if (abs($distance) <= $stepDistance) {
                    $this->coords[$axis] = $target[$axis];
                } else {
                    $this->coords[$axis] += $stepDistance * ($distance >= 0 ? 1 : -1);
                }
            }

            try {
                $this->mouse->mouseMove($rootCoords, round($this->coords['x'] + $correction['x']), round($this->coords['y'] + $correction['y']));
                usleep(round($pause * rand(80, 120) / 100));
            } catch (Facebook\WebDriver\Exception\MoveTargetOutOfBoundsException | MoveTargetOutOfBoundsException $e) {
                $this->logger->error('[MoveTargetOutOfBoundsException]: ' . $e->getMessage());
                $this->logger->debug("moving by x: " . round($this->coords['x'] + $correction['x']));
                $this->logger->debug("moving by y: " . round($this->coords['y'] + $correction['y']));
            }
        }

        $this->coords['x'] = floor($this->coords['x']);
        $this->coords['y'] = floor($this->coords['y']);

        $this->logger->debug("moved to {$this->coords['x']},{$this->coords['y']}");
	}

    /**
     * @param RemoteWebElement $element
     * @param array            $options
     * allowed options:
     *     'offset' => ['x' => 10, 'y' => 10], horizontal and vertica; offset from element center
     *     'x', 'y' - deprecated, unused
     */
	public function moveToElement($element, $options = ['x' => 20, 'y' => 5]) {
        $offsetFromCenter = $this->driver->getServerInfo()->isMouseOffetFromCenter();
	    if ($this->driver instanceof \RemoteWebDriver) {
	        // unsupported in new versions of webdriver
            $coords = $element->getCoordinates()->inViewPort();
        } else {
            $offsetFromCenter = true;
            $coords = $element->getLocation();
        }
        $size = $element->getSize();

        // calculate offset from top left corner of element
        if (isset($options['offset'])) {
            $xOffset = $options['offset']['x'];
            $yOffset = $options['offset']['y'];
        } else {
            $xOffsetMax = floor($size->getWidth() / 3);
            $xOffset = floor($size->getWidth() / 2) + rand(-$xOffsetMax, $xOffsetMax);
            $yOffsetMax = floor($size->getHeight() / 3);
            $yOffset = floor($size->getHeight() / 2) + rand(-$yOffsetMax, $yOffsetMax);
        }

        if ($offsetFromCenter) {
            $xOffset -= floor($size->getWidth() / 2);
            $yOffset -= floor($size->getHeight() / 2);
        }

        $distanceX = $this->coords['x'] - ($coords->getX() + $xOffset);
        $distanceY = $this->coords['y'] - ($coords->getY() + $yOffset);

        $movementSteps = round($this->steps * rand(80, 120) / 100);
        $pauseBetweenSteps = $this->duration / $movementSteps;

        $xStep = round($distanceX / $movementSteps);
        $yStep = round($distanceY / $movementSteps);

        try {
            for ($stepsLeft = $movementSteps; $stepsLeft > -1; $stepsLeft--) {
                $x = $xStep * $stepsLeft + $xOffset;
                $y = $yStep * $stepsLeft + $yOffset;
                $this->mouse->mouseMove($element->getCoordinates(), $x, $y);
                usleep(round($pauseBetweenSteps * rand(80, 120) / 100));
            }

            // make sure that rounding errors when moving by steps are corrected
            if ($x != $xOffset || $y != $yOffset) {
                $this->mouse->mouseMove($element->getCoordinates(), $xOffset, $yOffset);
            }
        } catch (Facebook\WebDriver\Exception\MoveTargetOutOfBoundsException | MoveTargetOutOfBoundsException $e) {
            $this->logger->error("MoveTargetOutOfBoundsException: {$e->getMessage()}\ntrace: {$e->getTraceAsString()}", ['pre' => true]);
            $this->mouse->mouseMove($element->getCoordinates(), $xOffset, $yOffset); // mouse still be moved where it needs to, just without steps
        }

        $this->coords = ['x' => $coords->getX() + $xOffset, 'y' => $coords->getY() + $yOffset]; // saving new coords
	}

	public function click()
	{
		$this->logger->info("clicking to: {$this->coords['x']},{$this->coords['y']}");
		$this->mouse->mouseDown();
		usleep(rand(100000, 800000));
		$this->mouse->mouseUp();
	}

    /**
     * @param RemoteWebElement $element
     */
	public function sendKeys($element, $text, $cps = 3)
	{
	    $avgDelay = 1000000 / $cps;
		$this->logger->info("sending keys: {$text}");
		for($n = 0; $n < mb_strlen($text); $n++){
			$element->sendKeys(mb_substr($text, $n, 1));
			usleep(rand(round($avgDelay * 0.7), round($avgDelay * 1.3)));
		}
    }

    public function setCoords(int $x, int $y) {
        $this->coords['x'] = $x;
        $this->coords['y'] = $y;
    }

	private function detectCorrection() : array
    {
        $this->logger->notice(__METHOD__);
        $root = $this->getRootElement();
        $rootSize = $root->getSize();
        $this->logger->info("body size: {$rootSize->getWidth()},{$rootSize->getHeight()}");

        $backupName = "b" . bin2hex(random_bytes(random_int(4,10)));
        $coordsName = "c" . bin2hex(random_bytes(random_int(4,10)));

        $this->driver->executeScript("
            window.{$backupName} = document.onmousemove;
            document.onmousemove = function(e) {
                window.{$coordsName} = {'x': e.pageX, 'y': e.pageY};
            };
        ");

        try {
            $this->mouse->mouseMove($root->getCoordinates(), floor($this->coords['x']), floor($this->coords['y']));
        } catch (Facebook\WebDriver\Exception\MoveTargetOutOfBoundsException | MoveTargetOutOfBoundsException $e) {
            $this->logger->error('[MoveTargetOutOfBoundsException]: ' . $e->getMessage());
            $this->logger->debug("moving by x: " . floor($this->coords['x']));
            $this->logger->debug("moving by y: " . floor($this->coords['y']));
        }

        $coords = $this->driver->executeScript("
            document.onmousemove = window.{$backupName};
            delete window.{$backupName};
            return window.{$coordsName};
        ");

        if (!$coords) {
            $coords = ['x' => 0, 'y' => 0];
        }

        $this->logger->info("moved to {$this->coords['x']},{$this->coords['y']} - visible from browser as {$coords['x']},{$coords['y']}");

        $correction = [
            'x' => $this->coords['x'] - $coords['x'],
            'y' => $this->coords['y'] - $coords['y'],
        ];

        if ($correction['x'] == $this->coords['x']) {
            $this->logger->warning("failed to detect correction. using zero correction");
            $correction = ['x' => 0, 'y' => 0];
        }

        // correct cast when body width is uneven
        // for example body width is 1001. we will get $this->correction['x'] = -501
        // that will results in MoveTargetOutOfBoundsException: (-0.5, 100) is out of bounds
        // when moving to 0, 100
        // so add 1 to compensate
        $diffX = $rootSize->getWidth() / 2 + $correction['x'];
        if ($diffX < 0.0 && $diffX > -1) {
            $this->logger->info("added 0.5 to x-correction");
            $correction['x'] += 0.5;
        }
        $diffY = $rootSize->getHeight() / 2 + $correction['y'];
        if ($diffY < 0.0 && $diffY > -1) {
            $this->logger->info("added 0.5 to y-correction");
            $correction['y'] += 0.5;
        }

        $this->logger->info("detected correction: {$correction['x']},{$correction['y']}");
        return $correction;
    }

    /**
     * @return RemoteWebElement
     */
    private function getRootElement()
    {
        return $this->driver->findElement(WebDriverBy::xpath('//body'));
    }

}
