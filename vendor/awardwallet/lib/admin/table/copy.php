<?
require( "../../../kernel/public.php" );
require_once( "$sPath/kernel/TForm.php" );
require_once( "$sPath/kernel/TSchemaManager.php" );

$schemaManager = new TSchemaManager();

require( "$sPath/lib/admin/design/header.php" );

echo "<h1>Copy table row with all dependencies</h1>";

$form = new TBaseForm(array(
	"Table" => array(
		"Type" => "string",
		"Size" => 40,
		"Required" => true,
		"Value" => "Usr",
	),
	"ID" => array(
		"Type" => "integer",
		"Required" => true,
		"Caption" => "ID",
	),
	"Changes" => array(
		"Type" => "string",
		"Size" => 4000,
		"InputType" => "textarea",
		"Required" => false,
		"Note" => "Changes to Master record",
		"HTML" => true,
		"Value" => "Login=NewLogin\nEmail=newemail@gmail.com\nPass=".md5(TEST_PASSWORD)."\nRefCode=".RandomStr(ord('a'), ord('z'), 10),
	),
	"Preview" => array(
		"Type" => "boolean",
		"Required" => true,
		"Value" => "1",
	),
));
$form->SubmitButtonCaption = "Copy table row";
$form->OnCheck = "checkForm";

if($form->IsPost && $form->Check()){
	$q = new TQuery("select * from {$form->Fields['Table']['Value']}
	where ".$Connection->PrimaryKeyField($form->Fields['Table']['Value'])." = {$form->Fields['ID']['Value']}" );
	$qAgents = new TQuery("select ua.UserAgentID, au.UserAgentID as BackID from UserAgent ua
	left outer join UserAgent au on ua.AgentID = au.ClientID and ua.ClientID = au.AgentID
	where ua.AgentID = {$form->Fields['ID']['Value']} and ua.ClientID is not null");
	$excludeRows = array();
	while(!$qAgents->EOF){
		$excludeRows[] = "UserAgent_".$qAgents->Fields['UserAgentID'];
		$excludeRows[] = "UserAgent_".$qAgents->Fields['BackID'];
		$qAgents->Next();
	}
	$rows = $schemaManager->ChildRows($form->Fields['Table']['Value'], $q->Fields, $excludeRows);
	$rows[] = array(
		"Table" => $form->Fields['Table']['Value'],
		"ID" => $form->Fields['ID']['Value'],
		"Files" => $schemaManager->RowFiles($form->Fields['Table']['Value'], $q->Fields),
	);
	$rows = array_reverse($rows);
	$schemaManager->loadRows($rows);
	foreach(explode("\n", $form->Fields['Changes']['Value']) as $change){
		$pair = explode("=", trim($change));
		$rows[0]['Values'][$pair[0]] = $pair[1];
	}
	if($form->Fields['Preview']['Value'] == '1'){
		echo "<pre>".htmlspecialchars(var_export($rows, true))."</pre>";
	}
	else
		$schemaManager->CopyRows($rows);
}

echo $form->HTML();

require( "$sPath/lib/admin/design/footer.php" );

function checkForm(){
	global $form, $schemaManager, $Connection;
	if(!isset($schemaManager->Tables[$form->Fields['Table']['Value']]))
		return "Table not found";
	$q = new TQuery("select * from {$form->Fields['Table']['Value']}
	where ".$Connection->PrimaryKeyField($form->Fields['Table']['Value'])." = ".intval($form->Fields['ID']['Value']));
	if($q->EOF)
		return "Row with this ID not found in table";
	return null;
}

