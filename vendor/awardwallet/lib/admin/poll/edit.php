<?

require( "../../../kernel/public.php" );
require( "$sPath/kernel/TForm.php" );
$nID = $QS["ID"];
define( "MAX_OPTIONS", 8 );

require( "fields.php" );

// fill otpions
$q = new TQuery( "select po.*,
  case when p.CorrectAnswerID = po.PollOptionID then 1 else 0 end as CorrectAnswer
  from PollOption po, Poll p where p.PollID = po.PollID and po.PollID = $nID order by po.SortIndex" );
for( $n = 1; $n <= MAX_OPTIONS; $n++ )
{
  $arFields["Option{$n}Name"] = array( 
          "Type" => "string",
          "Size" => 250,
          "Caption" => "Option $n",
          "Required" => False,
          "Database" => False,
          "UnformattedHTML" => True,
          "CorrectAnswer" => ( ( $_SERVER["REQUEST_METHOD"] == "POST" ? intval( isset( $_POST["CorrectAnswer"] ) && ( intval( $_POST["CorrectAnswer"] ) == $n ) ) : ( !$q->EOF ? $q->Fields["CorrectAnswer"] : 0 ) ) ),
          "Value" => ( !$q->EOF ? $q->Fields["Name"] : "" ),
          "OnGetHTML" => "GetOptionsHTML" );
  $arFields["Option{$n}SortIndex"] = array( 
          "Type" => "integer",
          "Caption" => "Option $n Index",
          "UnformattedHTML" => True,
          "Required" => True,
          "Database" => False,
          "Value" => ( !$q->EOF ? $q->Fields["SortIndex"] : $n * 10 ),
          "OnGetHTML" => "GetOptionsHTML" );
  $arFields["Option{$n}Votes"] = array( 
          "Type" => "integer",
          "Caption" => "Option $n Votes",
          "UnformattedHTML" => True,
          "Required" => True,
          "Database" => False,
          "Value" => ( !$q->EOF ? $q->Fields["Votes"] : 0 ),
          "OnGetHTML" => "GetOptionsHTML" );
  if( !$q->EOF )
    $q->Next();
}

$arFields["OnlyUsersVote"] = array( 
        "Type" => "boolean",
        "Caption" => "Must be logged in to participate in Poll/Trivia",
        "Required" => True );
$arFields["OnlyUsersView"] = array( 
        "Type" => "boolean",
        "Caption" => "Must be logged in to view Poll/Trivia",
        "Required" => True );
$arFields["OnlyOneVote"] = array( 
        "Type" => "boolean",
        "Caption" => "Can vote only once",
        "Required" => True );

$objForm = New TForm( $arFields );

if( $nID == 0 )
{
  $sTitle = "Add poll/trivia";
  $objForm->SubmitButtonCaption = "Add";
  $objForm->SQLParams["CreationDate"] = $Connection->DateTimeToSQL( time() );
}
else
{
  $sTitle = "Edit poll/trivia";
  $objForm->SubmitButtonCaption = "Update";
}
$sCurrentPage = "";

require( "../design/header.php" );

$objForm->Connection = $Connection;
$objForm->TableName = "Poll";
$objForm->KeyField = "PollID";
$objForm->Uniques = array( 
  array( 
    "Fields" => array( "Name" ),
    "ErrorMessage" => "This poll already exists. Please choose another Name."
 )
);
$objForm->OnCheck = "CheckOptions";
$objForm->OnSave = "SaveOptions";

$objForm->Edit();

require( "../design/footer.php" );

// ------------------------------------------------------------------------------------
// проверяет опции
function CheckOptions()
{
  global $objForm;
  $bEmpty = True;
  for( $n = 1; $n <= MAX_OPTIONS; $n++ )
    if( $objForm->Fields["Option{$n}Name"]["Value"] != "" )
      $bEmpty = False;
  if( $bEmpty )
    return "Fill in one or more options";
  if( isset( $_POST["CorrectAnswer"] ) )
  {
    $nCorrectAnswer = intval( $_POST["CorrectAnswer"] );
    if( $objForm->Fields["Option{$nCorrectAnswer}Name"]["Value"] == "" )
      return "You can't select empty option as correct answer";
  }
  if( ( $objForm->Fields["IsTrivia"]["Value"] == "1" ) && !isset( $_POST["CorrectAnswer"] ) )
    return "Select correct answer for trivia";
  return NULL;
}

// ------------------------------------------------------------------------------------
// сохраняет опции
function SaveOptions( $nID )
{
  global $Connection, $objForm;
  $q = new TQuery();
  $arNames = array();
  $sCorrectAnswer = "";
  for( $n = 1; $n <= MAX_OPTIONS; $n++ )
  {
    if( $objForm->Fields["Option{$n}Name"]["Value"] != "" )
    {
      $sSQLName = $objForm->SQLValue( "Option{$n}Name" );
      $q->Open( "select * from PollOption where PollID = $nID and Name = $sSQLName" );
      if( $q->EOF )
        $Connection->Execute( "insert into PollOption( PollID, Name, SortIndex, Votes )
          values( $nID, $sSQLName, 
          {$objForm->Fields["Option" . $n . "SortIndex"]["Value"]}, 
          {$objForm->Fields["Option" . $n . "Votes"]["Value"]} )" );
      else
        $Connection->Execute( "update PollOption set 
          SortIndex = {$objForm->Fields["Option" . $n . "SortIndex"]["Value"]}, 
          Votes = {$objForm->Fields["Option" . $n . "Votes"]["Value"]} 
          where PollID = $nID and Name = $sSQLName" );
      $q->Close();
      $arNames[] = $sSQLName;
      if( isset( $_POST["CorrectAnswer"] ) && ( $n == intval( $_POST["CorrectAnswer"] ) ) )
        $sCorrectAnswer = $sSQLName;
    }
  }
  if( count( $arNames ) > 0 )
  {
    $sNameList = implode( ", ", $arNames );
    $Connection->Execute( "update Poll set CorrectAnswerID = null where CorrectAnswerID in( select PollOptionID from PollOption where PollID = $nID and Name in ( $sNameList ) )" );
    $Connection->Execute( "delete from PollOption where PollID = $nID and Name not in( $sNameList )" );
    if( $sCorrectAnswer != "" )
    {
      $q->Open( "select PollOptionID from PollOption where PollID = $nID and Name = $sCorrectAnswer" );
      $Connection->Execute( "update Poll set CorrectAnswerID = {$q->Fields["PollOptionID"]} where PollID = $nID" );
      $q->Close();
    }
  }
  else
  {
    $Connection->Execute( "update Poll set CorrectAnswerID = null where CorrectAnswerID in( select PollOptionID from PollOption where PollID = $nID )" );
    $Connection->Execute( "delete from PollOption where PollID = $nID" );
  }
}

// ------------------------------------------------------------------------------------
// возвращает html формы
function GetOptionsHTML( $sField, $arField )
{
  global $objForm;
  if( $sField != "Option1Name" )
    return "";
  $sResult = "";
  for( $n = 1; $n <= MAX_OPTIONS; $n++ )
  {
    $sResult .= "<tr>
      <td>Option $n: </td>
      <td>
        <input type=text name=Option{$n}Name maxlength=250 size=60 value=\"{$objForm->Fields["Option" . $n . "Name"]["Value"]}\">
        Order <input type=text name=Option{$n}SortIndex maxlength=10 size=5 value=\"{$objForm->Fields["Option" . $n . "SortIndex"]["Value"]}\">
        Votes <input type=text name=Option{$n}Votes maxlength=10 size=5 value=\"{$objForm->Fields["Option" . $n . "Votes"]["Value"]}\">
        <input type=radio name=CorrectAnswer value=\"$n\"" . ( $objForm->Fields["Option{$n}Name"]["CorrectAnswer"] ? " checked" : "" ) . "> Correct answer
      </td>
    </tr>";
  }
  return $sResult;
}


?>
