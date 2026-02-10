<?php

interface ObservableInterface {
	
	/**
	 * @param callback $observer
	 * @param string $eventType
	 */
	public function addObserver($observer, $eventType);

	public function fireEvent($eventType);
	
}

?>