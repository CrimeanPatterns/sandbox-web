<?

$arFields = array( 
  "IsTrivia" => array( 
        "Type" => "boolean",
        "Caption" => "This is Trivia",
        "Required" => True ),
  "IsOpen" => array( 
        "Type" => "boolean",
        "Caption" => "Trivia/Poll is open",
        "Required" => True ),
  "Name" => array( 
        "Type" => "string",
        "Size" => 250,
        "Required" => True ),
  "Location" => array( 
        "Type" => "string",
        "Size" => 250,
        "Required" => False ),
  "Description" => array( 
        "Type" => "string",
        "Size" => 250,
        "InputType" => "textarea",
        "Required" => False ),
  "Question" => array( 
        "Type" => "string",
        "Size" => 250,
        "Required" => True )
);

?>