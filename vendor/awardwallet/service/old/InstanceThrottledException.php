<?php

class InstanceThrottledException extends Exception implements CheckAccountExceptionInterface {

    public function throwToParent(){
        return true;
    }

};
