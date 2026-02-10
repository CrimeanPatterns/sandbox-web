<?php

interface HttpPluginInterface {

	public function onRequest(HttpDriverRequest $request);

	/**
	 * return new request or null
	 * @param HttpDriverResponse $response
	 * @return HttpDriverRequest|null
	 */
	public function onResponse(HttpDriverResponse $response);

} 