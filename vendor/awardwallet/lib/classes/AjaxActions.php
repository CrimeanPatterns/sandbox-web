<?

class AjaxActions{
	// Response type: json,string,xml(not implemented)
	var $responseMethod = 'string';
	
	/**
	 * Sending response
	 * string - @data = string
	 * json - @data = array()
	 * 
	 */ 
	function sendResponse($data){
		switch($this->responseMethod){
			case 'string':{
				echo $data;
			} break;
			case 'json':{
				if(!is_array($data) && !empty($data)){
					$data = array(
						'error' => 1,
						'message' => $data
					);
				}
				if (!isset($data['error']))
					$data['error'] = 0;
				$arResponse = $data;
				header("Content-type: application/json");
				echo json_encode( $arResponse );
				exit();
			} break;
		}
	}
	
	/**
	 * check errors 
	 * @errors = array()
	 */ 
	function checkErrors($errors = array()){
		if(isset($errors['isLogin'])){
			if( !isset( $_SESSION['UserID'] ) )
				SendResponse("Not logged in");
		}
	}
	
}