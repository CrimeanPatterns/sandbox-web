<?

class CancelCheckException extends Exception implements CheckAccountExceptionInterface {

	public function throwToParent(){
		return true;
	}

}

