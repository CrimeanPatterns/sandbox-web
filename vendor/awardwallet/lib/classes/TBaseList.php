<?php

// -----------------------------------------------------------------------
// base list class
//		lists query results as html, using pages
//		show links to editing. allow deleting.
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------
// Draw -> OpenQuery -> CreateFilterForm ->

require_once __DIR__ . "/../listFunctions.php";

class TListFilterForm extends TBaseForm
{
    // check one form field
    public function CheckField($sFieldName, &$arField)
    {
        $bProcess = false;

        if (isset($arField["Value"])) {
            if (is_array($arField["Value"])) {
                $sValue = implode(",", $arField["Value"]);
            } else {
                $sValue = trim($arField["Value"]);
            }

            if ((strlen($sValue) > 1) && !isset($arField['Options'])) {
                $sSign = substr($sValue, 0, 1);

                if (in_array($sSign, [">", "<"])) {
                    $sStoredValue = $arField["Value"];
                    $arField["Value"] = substr($sValue, 1);
                    $bProcess = true;
                }
            }
        }
        $bResult = parent::CheckField($sFieldName, $arField);

        if ($bProcess) {
            $arField["Value"] = $sStoredValue;
        }

        return $bResult;
    }
}

class TBaseList
{
    private const NULL_NUMBER = PHP_INT_MAX;

    // data table name
    public $Table = null;
    // key field of table
    public $KeyField = null;
    /* columns array
    $Fields = array(
        "FieldName1" => array(
            "Caption" => "Field Caption",		column caption.	default is NameToText( FieldName ),
                                                see functions.php for details
            "Type" => "string",					default "string",
                                                can be "string", "date", "datetime", "money", "url",
                                                suitable for formatting dates, when output
            "Sort" => "FieldName1, FieldName2", sort expression. used in order by statement
                                                default same as field name.
                                                used to sort by LastName, then FirstName
                                                unset, to disable sorting on this field
            "AnchorAttributes" => string,		additional attributes for <A> tag, when Type=url,
                                                f.x. "target=_blank",
            "InplaceEdit" => true/false			edit field in list, true by default, requires TList->InplaceEdit to be true
            "FilterType" => "where" or "having", default "where"
        ),
        "FieldName2" => ... */
    public $Fields;

    // default table for column filter %table_name%.%column_name%
    public $FilterTable;
    // will be set automatically
    public $URLParamsString;
    // sql. created automatically in constructor. you can overwrite constructed statement.
    // should not contain order by (will be appended automatically)
    public $UnwantedURLParams = ["Page", "ID", "Copy", "Preselected", "FormToken"];
    public $SQL;
    // group by
    public $groupBy = null;
    // readonly mode, boolean
    public $ReadOnly = false;
    // if set to true the delete button will show up...
    public $AllowDeletes = false;
    // array of queries, which will be executed before deleting record
    // useful to delete records from child tables
    // should contain parameter [ID]
    public $DeleteQueries;
    // default sort, field name
    public $DefaultSort;
    public $DefaultSort2;
    // filters, array ( name => SQL condition ). added to sql.
    public $Filters;
    // custom sorts, in addition to fields, array( "SortName1" => array( "OrderBy" => "Field1 desc", "Caption" => "Sort1 Caption" ), "SortName2" => array ..
    public $Sorts = [];
    // active primary sort, sort name, key of Sorts array
    public $Sort1;
    // active secondary sort, sort name, key of Sorts array
    public $Sort2;
    // can add
    public $CanAdd = false;
    // show editors and save button
    public $ShowEditors = false;
    // record limit
    public $Limit = null;
    /**
     * @var TQuery query object. created automatically
     */
    public $Query;
    // page navigator string.  created auomatically
    public $PageNavigator;
    // empty list message
    public $EmptyListMessage = "There are no records to display at this point.";
    // show total number of records?
    public $ShowTotals = false;
    // totals string. autofilled.
    public $Totals;
    // divide to pages?
    public $UsePages = true;
    // table parameters
    public $tableParams = " width='100%' class='detailsTable' id='list-table'";
    // show filters
    public $ShowFilters = false;
    // filter form
    public $FilterForm;
    // show / hide top navigation
    public $showTopNav = true;
    // page size
    public $PageSize;
    // multiple edit
    public $MultiEdit = true;
    // allowed page sizes
    public $PageSizes = ["10" => "10", "20" => "20", "30" => "30", "40" => "40", "50" => "50", "100" => "100"];
    // category manager
    public $CategoryExplorer;
    public $CategoryLink = "<br>";
    //header color of the list
    public $headerColor = FORM_TITLE_COLOR;
    // external delete script
    public $ExternalDelete;
    // show flat category list - usefull when there are many nested categories
    public $bShowFlatCategoryList = true;
    // original, non-formatted query fields
    public $OriginalFields;
    // show import/export buttons
    public $ShowImport = true;
    // show import/export buttons
    public $ShowExport = true;
    // associated schema object
    public $Schema = null;
    // page-size, sort form action
    public $FormAction;
    // edit records right in list
    public $InplaceEdit = false;
    public $InplaceFormClass = "TForm";
    public $ShowBack = false;
    public $AlwaysShowEditLinks = false;
    // display buttons on top
    public $TopButtons = true;
    // name of file (w/o extension) for exporting to csv
    public $ExportName;
    /* flag for exactly WHERE in query
    null = automatic detection
    true = precise statement
    false = exact negation */
    public $isWhere = null;
    // export column names to CSV
    public $ExportCSVHeader = true;
    // preselected ids
    public $Preselected = [];

    // events
    // receives parameter RecordID
    public $OnDelete;
    /**
     * @var int
     */
    public $repeatHeadersEveryNthRow;
    /* @var bool - show copy link on rows */
    public $showCopy = false;
    // columns count, calculated in draw method
    protected $ColCount;
    /**
     * @var \Symfony\Component\HttpFoundation\Request
     */
    protected $request;
    /** @var string */
    private $filterUrl;

    public function __construct($table, $fields, $defaultSort = null, Symfony\Component\HttpFoundation\Request $request = null)
    {
        global $Connection;

        $this->request = $request;

        if ($this->request === null) {
            $this->request = getSymfonyContainer()->get("request_stack")->getMasterRequest();
        }

        if ($this->request === null) {
            $this->request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
        }

        $this->Fields = $fields;
        $this->Table = $table;
        $this->KeyField = $Connection->PrimaryKeyField($table);
        $arSQLFields = [];

        foreach ($this->Fields as $sField => $arField) {
            if (ArrayVal($arField, "Database", true)) {
                $arSQLFields[] = $sField;
            }
        }

        if (!in_array($this->KeyField, $arSQLFields)) {
            $arSQLFields[] = $this->KeyField;
        }
        $this->SQL = "select " . implode(", ", $arSQLFields) . " from $table";
        $this->CompleteFields();

        if ($defaultSort === null) {
            foreach ($fields as $sField => $arField) {
                if (ArrayVal($arField, "Sort", true) && ArrayVal($arField, "Database", true)) {
                    $defaultSort = $sField;

                    break;
                }
            }
        }

        $this->DefaultSort = $defaultSort;
        $this->BuildDeleteQueries();
        $this->FormAction = $this->request->server->get('REQUEST_URI');
        $this->filterUrl = preg_replace('#\?.*$#ims', '', $this->FormAction);

        if ($preselected = $this->request->query->get("Preselected")) {
            $this->Preselected = explode(",", $preselected);
        }
    }

    public function TBaseList($sTable, $arFields, $sDefaultSort, Symfony\Component\HttpFoundation\Request $request = null)
    {
        $this->__construct($sTable, $arFields, $sDefaultSort, $request);
    }

    // check fields. set defaults.
    public function CompleteFields()
    {
        foreach ($this->Fields as $sFieldName => &$arField) {
            if (!isset($arField["Sort"])) {
                if (ArrayVal($arField, "Database", true)) {
                    $arField["Sort"] = $sFieldName;
                } else {
                    $arField["Sort"] = false;
                }
            }

            if ($arField["Sort"] && !preg_match("/\s((ASC)|(DESC))/i", $arField["Sort"])) {
                $arField["Sort"] = str_replace(",", " ASC,", $arField["Sort"]) . " ASC";
            }

            if (!isset($arField["Caption"])) {
                $arField["Caption"] = NameToText($sFieldName);
            }

            if (!isset($arField["Type"])) {
                $arField["Type"] = "string";
            }

            if (!isset($arField["DisplayFormat"])) {
                $arField["DisplayFormat"] = "string";
            }

            if (!isset($arField["InplaceEdit"])) {
                $arField["InplaceEdit"] = ($sFieldName != $this->KeyField);
            }

            if (!isset($arField["HTML"])) {
                $arField["HTML"] = false;
            }
            //if( !in_array( $arField["Type"], array( "string", "date", "datetime", "money" ) ) )
            //	DieTrace( "TList->CompleteFields: Invalid type {$arField["Type"]} for field $sFieldName" );
        }
    }

    // create delete queries
    public function BuildDeleteQueries()
    {
        $this->DeleteQueries = [];

        foreach ($this->Fields as $arField) {
            if (isset($arField["Manager"])) {
                $objManager = $arField["Manager"];
                $sClass = get_class($objManager);

                if (($sClass == "TTableLinksFieldManager")
                || (is_subclass_of($objManager, "TTableLinksFieldManager"))) {
                    $this->DeleteQueries[] = "delete from {$objManager->TableName} where {$vKeyField} = [ID]";

                    if ($objManager->RelatedLink) {
                        $this->DeleteQueries[] = "delete from {$objManager->TableName} where " . array_pop(array_keys($objManager->Fields)) . " = [ID]";
                    }
                }
            }
        }
    }

    // delete record
    // override to delete childs
    public function Delete($nID)
    {
        global $Connection;

        if (isset($this->OnDelete)) {
            CallUserFunc($this->OnDelete + [$nID]);
        }

        if ($this->AllowDeletes) {
            // delete pictures
            foreach ($this->Fields as $arField) {
                if (isset($arField["Manager"])) {
                    $objManager = $arField["Manager"];
                    $sClass = get_class($objManager);

                    if (($sClass == "TPicturesFieldManager")
                    || (is_subclass_of($objManager, "TPicturesFieldManager"))) {
                        $objManager->KeyField = $Connection->PrimaryKeyField($objManager->TableName);
                        $objManager->DeletePictures($this->KeyField, $nID, []);
                    }
                }
            }
            // delete nested data
            foreach ($this->DeleteQueries as $sSQL) {
                $Connection->Execute(str_replace("[ID]", $nID, $sSQL));
            }
            // delete the row
            $Connection->Execute("delete from {$this->Table} where {$this->KeyField} = $nID");
        }
    }

    // save changes
    public function Update()
    {
        if (($_SERVER["REQUEST_METHOD"] != "POST") || $this->ReadOnly || !isset($_POST['action'])) {
            return;
        }
        $arIDs = [];

        foreach ($_POST as $sKey => $sValue) {
            if (preg_match("/^sel[\w_]+$/i", $sKey) && preg_match("/^[\w\, ]+$/i", $sValue)) {
                $arIDs[] = $sValue;
            }
        }
        $this->ProcessAction($_POST["action"], $arIDs);
    }

    public function ProcessAction($action, $ids)
    {
        switch ($action) {
            case "delete":
                if (isset($this->ExternalDelete)) {
                    $data = ["Table" => $this->Table, "ID" => implode(",", $ids), "BackTo" => $_SERVER['REQUEST_URI']];

                    if (isset($this->Schema)) {
                        $data['Schema'] = $this->Schema->Name;
                    }
                    PostRedirect($this->ExternalDelete, $data);
                } else {
                    foreach ($ids as $id) {
                        $this->Delete($id);
                    }
                }

                break;
        }
    }

    public function SaveInplaceForm($sSQL)
    {
        $this->InplaceForm->CalcSQLValues();
        $q = $this->createQuery($sSQL);

        while (($this->UsePages && !$q->EndOfPage()) || (!$this->UsePages && !$q->EOF)) {
            $this->SaveInplaceFormRow($q->Fields);
            $q->Next();
        }
    }

    public function SaveInplaceFormRow(&$arRow)
    {
        global $Connection, $Interface;
        $arFields = [];

        foreach ($this->InplaceForm->Fields as $sField => $arField) {
            if (isset($arField["KeyField"]) && ($arField["KeyField"] == $arRow[$this->KeyField]) && isset($arField["SQLValue"])) {
                $arFields[$arField["FieldName"]] = $arField["SQLValue"];
                $arRow[$arField["FieldName"]] = $arField["Value"];
            }
        }

        if (isset($this->Schema)) {
            $form = $this->Schema->CreateForm();

            foreach ($form->Uniques as $unique) {
                $keyValues = [];

                foreach ($unique["Fields"] as $fieldName) {
                    if (isset($arFields[$fieldName])) {
                        $keyValues[$fieldName] = $arFields[$fieldName];
                    } else {
                        $keyValues[$fieldName] = "'" . addslashes($arRow[$fieldName]) . "'";
                    }
                }
                $q = new TQuery("select 1 from {$this->Table} where " . ImplodeAssoc(" = ", " and ", $keyValues) . " and {$this->KeyField} <> {$arRow[$this->KeyField]}");
                //echo($q->SQL);
                //echo "<hr>";
                if (!$q->EOF) {
                    $Interface->drawMessage("Failed to save row {$arRow[$this->KeyField]}: {$unique['ErrorMessage']}", "error");

                    return false;
                }
            }
        }
        $Connection->Execute(UpdateSQL($this->Table, [$this->KeyField => $arRow[$this->KeyField]], $arFields));

        return true;
    }

    public function DrawInplaceEdit($sFieldName, &$arField)
    {
        $arFieldValues = &$this->Query->Fields;
        $sEdit = $sFieldName . "_" . $arFieldValues[$this->KeyField];

        if (isset($this->InplaceForm->Fields[$sEdit])) {
            $sClass = "";

            if (!isset($this->InplaceForm->Fields[$sEdit]["Manager"])) {
                $sHTML = $this->InplaceForm->InputHTML($sEdit);
            } else {
                $objManager = &$this->InplaceForm->Fields[$sEdit]["Manager"];
                $sHTML = $objManager->InputHTML($sEdit);
            }

            if (isset($this->InplaceForm->Fields[$sEdit]["Error"])) {
                echo "<td class=formErrorCell><span class=formerror>{$this->InplaceForm->Fields[$sEdit]["Error"]}</span><br>" . $sHTML . "</td>";
            } else {
                echo "<td>" . $sHTML . "</td>";
            }
        } else {
            echo "  <td>{$arFieldValues[$sFieldName]}</td>\n";
        }
    }

    // draw fields, in data row
    public function DrawFields()
    {
        $arFieldValues = &$this->Query->Fields;

        foreach ($this->Fields as $sFieldName => &$arField) {
            if ($this->InplaceEdit) {
                $this->DrawInplaceEdit($sFieldName, $arField);
            } else {
                echo "  <td>{$arFieldValues[$sFieldName]}</td>\n";
            }
        }
    }

    // formats field header, add sort links
    public function FormatCaption($sCaption, $sField)
    {
        if ($this->Fields[$sField]["Type"] == "customCode") {
            return $sCaption;
        }

        $isReverse = $this->request->query->get("SortOrder") === "Reverse";

        if (isset($this->Fields[$sField]) && $this->Fields[$sField]["Sort"]) {
            $arParams = $this->request->query->all();
            $arParams["Sort1"] = $sField;
            $arParams["Sort2"] = $this->Sort1;

            foreach ($this->UnwantedURLParams as $key) {
                unset($arParams[$key]);
            }

            if (($this->Sort1 == $sField) && !$isReverse) {
                $arParams["SortOrder"] = "Reverse";
            } else {
                $arParams["SortOrder"] = "Normal";
            }
            $sCaption = "<a rel=\"nofollow\" class=a11pxBldWht style='font-weight: normal;' href=" . $this->filterUrl . "?" . ImplodeAssoc("=", "&", $arParams, true) . ">$sCaption</a>";

            if ($this->Sort1 == $sField) {
                if (!$isReverse) {
                    $image = "arrowDown1.gif";
                } else {
                    $image = "arrowUp1.gif";
                }
                $sCaption = "<table border='0' cellpadding='0' cellspacing='0'><tr><td>$sCaption</td><td><img width='7' height='4' src='/lib/images/{$image}' border='0' style='margin-bottom: 2px; margin-left: 7px;'/></td></tr></table>";
            }
        }

        return $sCaption;
    }

    // draws table title
    public function DrawFieldHeaders()
    {
        foreach ($this->Fields as $sField => &$arField) {
            echo "  <td class='white'>" . $this->FormatCaption($arField["Caption"], $sField) . "</td>\n";
        }
    }

    public function ExportTXTRow($arValues)
    {
        foreach ($arValues as $nKey => $sValue) {
            $sValue = str_replace("\t", " ", $sValue);
            $sValue = str_replace("\r", "\\r", $sValue);
            $sValue = str_replace("\n", "\\n", $sValue);
            //$sValue = str_replace("\"", "\"\"",$sValue);
            //$arValues[$nKey] = "\"".$sValue."\"";
            $arValues[$nKey] = $sValue;
        }
        echo implode("\t", $arValues) . "\r\n";
    }

    public function GetExportParams(&$arCols, &$arCaptions)
    {
        if (!isset($this->ExportName) && isset($this->Schema)) {
            $this->ExportName = $this->Schema->Name;
        }

        if (!isset($this->ExportName)) {
            DieTrace("ExportName property required for export");
        }
        $arCaptions = [];
        $arCols = $this->Fields;

        if (!isset($arCols[$this->KeyField])) {
            $arCols = [$this->KeyField => ["Caption" => $this->KeyField]] + $arCols;
        }

        foreach ($arCols as $sField => $arField) {
            $arCaptions[$sField] = $arField["Caption"];
        }
    }

    public function ExportTXT()
    {
        $this->GetExportParams($arCols, $arCaptions);
        header("Content-type: text/plain; charset=utf-8");
        header("Content-Disposition: attachment; filename={$this->ExportName}.txt");
        $this->ExportTXTRow($arCaptions);
        $this->OpenQuery();
        $objRS = &$this->Query;

        while (!$objRS->EOF) {
            $this->FormatFields("txt");
            $arValues = [];

            foreach (array_keys($arCols) as $sField) {
                $arValues[$sField] = ArrayVal($objRS->Fields, $sField);
            }
            $this->ExportTXTRow($arValues);
            $objRS->Next();
        }
    }

    public function ExportCSV()
    {
        $this->GetExportParams($arCols, $arCaptions);
        header("Content-type: text/csv; charset=utf-8");
        header("Content-Disposition: attachment; filename={$this->ExportName}.csv");

        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && ($_SERVER['HTTP_ACCEPT_LANGUAGE'] == 'ru')) {
            $sSeparator = ';';
        } else {
            $sSeparator = ',';
        }

        if ($this->ExportCSVHeader) {
            $this->ExportCSVRow($arCaptions, $sSeparator);
        }
        $this->UsePages = false;
        $this->InplaceEdit = false;
        $this->OpenQuery();

        while (!$this->Query->EOF) {
            $this->FormatFields("csv");
            $arValues = [];

            foreach (array_keys($arCols) as $sField) {
                $arValues[$sField] = ArrayVal($this->Query->Fields, $sField);
            }
            $this->FormatCSVRow($arValues);
            $this->ExportCSVRow($arValues, $sSeparator);
            $this->Query->Next();
        }
    }

    public function FormatCSVRow(&$values)
    {
    }

    public function ExportCSVRow($arValues, $sSeparator)
    {
        foreach ($arValues as $nKey => $sValue) {
            $sValue = str_replace("\"", "\"\"", $sValue);
            $sValue = "\"" . $sValue . "\"";
            $arValues[$nKey] = $sValue;
        }
        echo implode($sSeparator, $arValues) . "\r\n";
    }

    // create order by, for sorting
    public function GetOrderBy()
    {
        foreach ($this->Fields as $sField => $arField) {
            if ($arField["Sort"]) {
                $this->Sorts[$sField] = ["Caption" => $arField["Caption"], "OrderBy" => $arField["Sort"]];
                //				print "<script>alert('".$arField["Caption"]."')</script>";
            }
        }

        $sort1 = $this->request->query->get('Sort1');
        $sort2 = $this->request->query->get('Sort2');
        $isReverse = $this->request->query->get("SortOrder") === "Reverse";

        if (!isset($this->Sort1)) {
            if (isset($sort1)) {
                if (isset($this->Sorts[$sort1])) {
                    $this->Sort1 = $sort1;
                } elseif (isset($this->Sorts[$sort2])) {
                    $this->Sort1 = $sort2;
                } else {
                    $this->Sort1 = $this->DefaultSort;
                }
            } elseif (!isset($this->DefaultSort)) {
                DieTrace("Please set TList->DefaultSort");
            } else {
                $this->Sort1 = $this->DefaultSort;
            }
        }

        if (count($this->Sorts) == 0) {
            return "";
        }

        if (!isset($this->Sorts[$this->Sort1])) {
            DieTrace("TList->GetOrderBy: Invalid Sort1 property: Can't find {$this->Sort1} in Fields or Sorts array");
        }
        $sSort = $this->Sorts[$this->Sort1]["OrderBy"];

        if ($isReverse) {
            $sSort = str_replace("ASC", "[DS]", $sSort);
            $sSort = str_replace("DESC", "[AS]", $sSort);
            $sSort = str_replace("[DS]", "DESC", $sSort);
            $sSort = str_replace("[AS]", "ASC", $sSort);
        }

        if (!isset($this->Sort2)) {
            $this->Sort2 = $sort2;
        }

        if (!isset($this->Sorts[$this->Sort2])) {
            $this->Sort2 = null;
        }

        if (!isset($this->Sort2) && isset($this->DefaultSort2)) {
            $this->Sort2 = $this->DefaultSort2;
        }

        if (isset($this->Sort2) && ($this->Sort1 != $this->Sort2)) {
            $sSort .= ", " . $this->Sorts[$this->Sort2]["OrderBy"];
        }

        return " order by " . $sSort;
    }

    // get one field filters
    public function GetFieldFilter($sField, $arField)
    {
        $sFilters = "";

        if (isset($arField['FilterField'])) {
            $sField = $arField['FilterField'];
        }

        if ((int) $arField['Value'] === self::NULL_NUMBER) {
            return " and $sField is null";
        }

        switch ($arField["Type"]) {
            case "string":
                $sFilters .= " and $sField like '%" . addslashes($arField["Value"]) . "%'";

                break;

            case "integer":
            case "float":
            case "boolean":
                $sSign = substr($arField["Value"], 0, 1);

                if (in_array($sSign, [">", "<"])) {
                    $arField["Value"] = substr($arField["Value"], 1);
                } else {
                    $sSign = "=";
                }
                $sFilters .= " and $sField $sSign {$arField["Value"]}";

                break;

            case "date":
                $sSign = substr($arField["Value"], 0, 1);

                if (in_array($sSign, [">", "<"])) {
                    $arField["Value"] = substr($arField["Value"], 1);
                    $sFilters .= " and $sField $sSign {$arField["SQLValue"]}";
                } else {
                    $sFilters .= " and $sField >= {$arField["SQLValue"]} and $sField < date_add( {$arField["SQLValue"]}, interval 1 day )";
                }

                break;

            default:
                break;
        }

        return $sFilters;
    }

    // get sql filters
    public function GetFilters($filterType = "where")
    {
        $sFilters = "";

        if ($this->ShowFilters && $this->FilterForm->Check($this->request->query->all())) {
            $this->FilterForm->CalcSQLValues();

            foreach ($this->FilterForm->Fields as $sField => $arField) {
                if (ArrayVal($arField, "Value") != "" && ArrayVal($arField, 'FilterType', 'where') == $filterType) {
                    if (
                        isset($this->FilterTable)
                        && !isset($arField['FilterField'])
                        && 'where' === $filterType
                    ) {
                        $sField = $this->FilterTable . '.' . $sField;
                    }

                    $sFilters .= $this->GetFieldFilter($sField, $arField, $filterType);
                }
            }
        }

        if ($sFilters != "") {
            $sFilters = substr($sFilters, 4);
        }

        if (isset($this->CategoryExplorer) && $filterType == "where") {
            $sCatFilters = $this->CategoryExplorer->GetListFilters();

            if ($sCatFilters != "") {
                if ($sFilters != "") {
                    $sFilters .= " and ";
                }
                $sFilters .= $sCatFilters;
            }
        }

        return $sFilters;
    }

    // combine sql with selected filters. sql may contain [Filters] placeholder.
    public function AddFilters($sSQL)
    {
        $whereFilters = $this->GetFilters();

        if ($whereFilters != "") {
            if (strpos($sSQL, "[Filters]") === false) {
                if ($this->isWhere == null) {
                    if (stripos($sSQL, "where") === false) {
                        $sSQL .= " where";
                    } else {
                        $sSQL .= " and";
                    }
                } elseif ($this->isWhere === false) {
                    $sSQL .= " and";
                } else {
                    $sSQL .= " where";
                }
                $sSQL .= " " . $whereFilters;
            } else {
                $sSQL = str_replace("[Filters]", " and " . $whereFilters, $sSQL);
            }
        } else {
            $sSQL = str_replace("[Filters]", "", $sSQL);
        }
        $havingFilters = $this->GetFilters("having");

        if ($havingFilters != "") {
            $sSQL .= " having " . $havingFilters;
        }

        return $sSQL;
    }

    public function GetFilterFields()
    {
        $arFields = [];

        foreach ($this->Fields as $sFieldName => $arField) {
            if (ArrayVal($arField, "Database", true) && ArrayVal($arField, "AllowFilters", true)) {
                $arFormField = [
                    "Caption" => $arField["Caption"],
                    "Type" => $arField["Type"],
                    "HTML" => true,
                    "FilterType" => ArrayVal($arField, "FilterType", "where"),
                ];

                if (isset($arField['FilterField'])) {
                    $arFormField['FilterField'] = $arField['FilterField'];
                }

                if (!isset($arFormField["InputAttributes"])) {
                    $arFormField["InputAttributes"] = '';
                }

                if (isset($arField["filterWidth"])) {
                    $arFormField["InputAttributes"] .= " style='width: " . $arField["filterWidth"] . "px;";
                }
                $bAdd = true;

                switch ($arField["Type"]) {
                    case "string":
                    case "url":
                        if (!isset($arField["filterWidth"])) {
                            $arFormField["InputAttributes"] .= " style='width: 130px;";
                        }
                        $arFormField["Size"] = 50;

                        break;

                    case "integer":
                    case "float":
                    case "money":
                        if (!isset($arField["filterWidth"])) {
                            $arFormField["InputAttributes"] .= " style='width: 50px;";
                        }
                        $arFormField["Size"] = 10;

                        break;

                    case "date":
                    case "datetime":
                        if (!isset($arField["filterWidth"])) {
                            $arFormField["InputAttributes"] .= " style='width: 70px;";
                        }
                        $arFormField["Size"] = 11;
                        $arFormField["Type"] = "date";

                        break;

                    case "boolean":
                        if (!isset($arField["filterWidth"])) {
                            $arFormField["InputAttributes"] .= " style='width: 50px;";
                        }
                        $arFormField["Options"] = ["" => "", "0" => "No", "1" => "Yes"];
                        $arFormField["Size"] = 10;

                        break;

                    default:
                        $bAdd = false;

                        break;
                }

                if (isset($arField["Options"])) {
                    $arFormField["Options"] = $arField["Options"];

                    if (!isset($arFormField["Options"][""])) {
                        $arFormField["Options"] = ["" => ""] + $arFormField["Options"];
                    }

                    if (!($arField["Required"] ?? false)) {
                        ArrayInsert($arFormField["Options"], "", true, [
                            self::NULL_NUMBER => "<Empty>",
                        ]);
                    }

                    $arFormField["InputType"] = "select";

                    if (!isset($arField["filterWidth"])) {
                        $arFormField["InputAttributes"] .= " style='width: 70px;";
                    }
                }

                if ($bAdd) {
                    $arFormField["InputAttributes"] .= " font-family: Arial, Helvetica, sans-serif; font-weight: normal;'";
                    $arFields[$sFieldName] = $arFormField;
                }
            }
        }

        return $arFields;
    }

    // create filter form
    public function CreateFilterForm()
    {
        $this->FilterForm = new TListFilterForm($this->GetFilterFields());
    }

    // show active filters
    public function DrawFilters()
    {
        if (isset($this->FilterForm->Error)) {
            echo "<tr><td colspan=" . (count($this->Fields) + 1 + ($this->MultiEdit ? 1 : 0)) . " class=formErrorCell>{$this->FilterForm->Error}</td></tr>";
        }
        $arHiddens = $this->request->query->all();
        $this->DrawListLinks($arHiddens);
        $this->DrawFiltersForm($arHiddens);
        $arHiddens['FormToken'] = GetFormToken();

        foreach ($this->UnwantedURLParams as $key) {
            unset($arHiddens[$key]);
        }
        echo "<form method='get'  name='list_filter_form' id='list_filter_form'>\n";
        DrawHiddens($arHiddens);
        echo "</form>";
    }

    // draw filter form. override to show custom filters
    // unset shown values from $arHiddens
    public function DrawFiltersForm(&$arHiddens)
    {
        if (isset($this->CategoryExplorer)) {
            echo "<tr><td colspan=" . (count($this->Fields) + 1) . ">\n";
            echo "Categories: ";
            $this->CategoryExplorer->DrawDropdownFilters($arHiddens);
            echo "</td></tr>\n";
        }
        echo "<tr>\n";

        if ($this->MultiEdit && !$this->ReadOnly) {
            echo "<td>&nbsp;</td>";
        }

        foreach ($this->Fields as $sFieldName => $arField) {
            if (isset($this->FilterForm->Fields[$sFieldName])) {
                $field = &$this->FilterForm->Fields[$sFieldName];

                if (!isset($field['InputAttributes'])) {
                    $field['InputAttributes'] = '';
                } else {
                    $field['InputAttributes'] .= ' ';
                }
                $field['InputAttributes'] .= ' form="list_filter_form"';
            }
            $this->DrawFieldFilter($sFieldName, $arField);
            unset($arHiddens[$sFieldName]);
        }

        if (!$this->ReadOnly) {
            echo "<td align=center><a href='#' onclick=\"FilterForm = document.forms['list_filter_form']; clearForm(FilterForm); FilterForm.submit(); return false;\">Clear Filters</a></td>";
        }
        echo "</tr>\n";
    }

    // draw one field filter
    public function DrawFieldFilter($sFieldName, &$arField)
    {
        if (isset($this->FilterForm->Fields[$sFieldName])) {
            echo "<td nowrap";

            if (isset($this->FilterForm->Fields[$sFieldName]["Error"])) {
                echo " class=formErrorCell";
            }
            echo "><table class=noBorder><tr><td>" . $this->FilterForm->InputHTML($sFieldName) . "</td><td><input type=Image form='list_filter_form' name=s1 width=8 height=7 src='/lib/images/button1.gif' style='border: none; margin-bottom: 1px; margin-right: 0px;'></td></tr></table></td>";
        } else {
            echo "<td>&nbsp;</td>\n";
        }
    }

    // format resulting query fields
    public function FormatFields($output = "html")
    {
        global $Connection;
        $arFields = &$this->Query->Fields;
        $this->OriginalFields = $arFields;

        foreach ($this->Fields as $sField => &$arField) {
            if ((ArrayVal($arFields, $sField) != "") && isset($arField["Type"])) {
                unset($sFormat);
                switch ($arField["Type"]) {
                    case "date":
                        $sFormat = 'm/d/Y';

                        if (isset($arField["IncludeTime"])) {
                            unset($sFormat);
                        }
                        // no break
                    case "datetime":
                        if (!isset($sFormat)) {
                            $sFormat = 'm/d/Y H:i:s';
                        }
                        $d = $Connection->SQLToDateTime($arFields[$sField]);

                        if (!empty($d)) {
                            $arFields[$sField] = date($sFormat, $d);
                        } else {
                            $arFields[$sField] = "";
                        }

                        break;

                    case "money":
                        $arFields[$sField] = "\$" . number_format($arFields[$sField], 2, ".", ",");

                        break;

                    case "boolean":
                        if ($arFields[$sField] == "1") {
                            $arFields[$sField] = "Yes";
                        } else {
                            $arFields[$sField] = "No";
                        }

                        break;

                    default:
                        if ($output === "html") {
                            if (!$arField['HTML']) {
                                $arFields[$sField] = htmlspecialchars($arFields[$sField]);
                            }
                        }
                }
            }

            switch ($arField["DisplayFormat"]) {
                case "url":
                    $s = $arFields[$sField];

                    if (strpos(strtolower($s), "http://") === 0) {
                        $s = substr($s, 7);
                    }

                    if (isset($arField["AnchorAttributes"])) {
                        $sAttributes = " " . $arField["AnchorAttributes"];
                    } else {
                        $sAttributes = "";
                    }
                    $arFields[$sField] = "<a href=\"http://{$s}\"{$sAttributes}>{$s}</a>";

                    break;

                case "money":
                    $arFields[$sField] = "\$" . number_format($arFields[$sField], 2, ".", ",");

                    break;

                break;
            }

            if (isset($arField["Options"])) {
                $arFields[$sField] = ArrayVal($arField["Options"], $arFields[$sField], $arFields[$sField]);
            }

            if ($arField["Type"] == "customCode") {
                $arFields[$sField] = eval($arField["Value"]);
            }
        }
    }

    //number_format( $arFields["Registers"] / $arFields["Clicks"] * 100, 2, ".", "," ) . " %"
    // draw sort select
    public function DrawSortSelect($separator = "&nbsp;&nbsp;", $inputAttributes = " style=\"width: 164px;\"", $bIncludeForm = true)
    {
        if ($bIncludeForm) {
            echo "<form method=get action={$this->FormAction} style='margin-bottom: 0px; margin-top: 0px;'>";
        }
        echo "Sort by:" . $separator;
        $ar = $this->request->query->all();
        $ar["Page"] = $this->Query->Page;
        $ar["Sort2"] = $this->Sort1;
        $ar["SortOrder"] = "Normal";
        unset($ar["Sort1"]);
        $arSortOptions = [];

        foreach ($this->Sorts as $sCode => $arSort) {
            $arSortOptions[$sCode] = $arSort["Caption"];
        }
        $objForm = new TBaseForm([
            "Sort1" => [
                "Type" => "integer",
                "Required" => true,
                "InputAttributes" => " onchange=\"this.form.submit();\"" . $inputAttributes,
                "Caption" => "Sort",
                "InputType" => "select",
                "Value" => $this->Sort1,
                "Options" => $arSortOptions, ],
        ]);
        echo $objForm->InputHTML("Sort1");

        if ($bIncludeForm) {
            DrawHiddens($ar);
            echo "<input type=hidden name=Posted value=1>\n";
            echo "</form>\r\n";
        }
    }

    // draw page size select
    public function DrawPageSizeSelect()
    {
        echo "<form method=get action={$this->FormAction} style='margin-bottom: 0px; margin-top: 0px;' name=pagesize_form>Results per page: ";
        $ar = $this->request->query->all();
        unset($ar["PageSize"]);
        unset($ar["Page"]);
        $objForm = new TBaseForm([
            "PageSize" => [
                "Type" => "integer",
                "Required" => true,
                "InputAttributes" => " onchange=\"this.form.submit();\"",
                "Caption" => "Page size",
                "InputType" => "select",
                "Value" => $this->PageSize,
                "Options" => $this->PageSizes, ],
        ]);
        echo $objForm->InputHTML("PageSize");
        DrawHiddens($ar);
        echo "<input type=hidden name=Posted value=1>\n";
        echo "</form>\r\n";
    }

    // unified format for key of the checkbox
    public function formatKey($listId = '')
    {
        if (empty($listId)) {
            return '';
        } else {
            $a = explode(',', str_replace(' ', '', $listId));
            sort($a);

            return implode('_', $a);
            // old formatting for key
            //return preg_replace("/\W+/ims", "_", $listId);
        }
    }

    // draw one row
    public function DrawRow()
    {
        $trColor = $this->getRowColor();
        $sRowStyle = " bgcolor=\"{$trColor}\"";
        echo "<tr{$sRowStyle} onMouseOver='javascript:this.style.backgroundColor=\"#E1E1E1\"' onMouseOut='javascript:this.style.backgroundColor=\"{$trColor}\"'>\n";

        if (!$this->ReadOnly && $this->MultiEdit) {
            $key = $this->formatKey($this->OriginalFields[$this->KeyField]);
            $selected = "";

            if (isset($_POST["sel{$key}"]) || in_array($key, $this->Preselected)) {
                $selected = " checked";
            }
            echo "	<td nowrap><input type=checkbox name=\"sel{$key}\" value=\"{$this->OriginalFields[$this->KeyField]}\"{$selected}></td>\n";
        }
        $this->DrawFields();

        if (!$this->ReadOnly || $this->AlwaysShowEditLinks) {
            echo "	<td nowrap align=center>";
            echo $this->GetEditLinks();
            echo "</td>\n";
        }
        echo "</tr>\n";
    }

    public function drawPageDetails($vAlign = "top", $bTop = true)
    {
        ?>
<table border="0" width="100%" cellspacing="0" cellpadding="1">
<tr>
<td nowrap align=left width="33%" valign="<?php echo $vAlign; ?>"><?php
        if ($bTop) {
            $this->DrawSortSelect();
        } elseif ($this->UsePages) {
            $this->DrawPageSizeSelect();
        } ?></td>
<td width="33%" valign="<?php echo $vAlign; ?>">
<?php
        if ($this->UsePages && $this->PageNavigator != "") {
            echo $this->Totals;
        }
        echo PIXEL; ?>
</td>
<td align=right nowrap width="33%" valign="<?php echo $vAlign; ?>"><?php echo $this->PageNavigator; ?><?php echo PIXEL; ?></td>
</tr>
<?php
// begin - if category browser is used show all possible categories
        if ($bTop) {
            if (isset($this->CategoryExplorer) && $this->bShowFlatCategoryList) {
                //				$this->CategoryExplorer->showTree();
                echo "<tr><td colspan='3'>";
                $this->CategoryExplorer->GetOptions($arOptions, $arOptionAttributes, false, "#d4d4d4");
                $arQS = $this->request->query->all();

                for ($i = 1; $i < 10; $i++) {
                    unset($arQS["Cat$i"]);
                }
                $filterSelect = [
                    $this->CategoryExplorer->KeyField => [
                        "Caption" => "Jump to",
                        "InputAttributes" => "onchange=\"if(this.value != ''){document.location.href='" . $this->request->server->get('DOCUMENT_URI') . "?Cat1='+this.value+'&" . ImplodeAssoc("=", "&", $arQS, true) . "'}\"",
                        "Type" => "integer",
                        "InputType" => "select",
                        "Options" => $arOptions,
                        "OptionAttributes" => $arOptionAttributes,
                        "Value" => $this->request->query->get("Cat1"),
                        "Required" => true,
                    ],
                ];
                $objFilterSelect = new TForm($filterSelect);
                $objFilterSelect->CompleteFields();
                echo "Jump to: " . $objFilterSelect->InputHTML($this->CategoryExplorer->KeyField);
                echo "</td></tr>";
            }
        }
        // end - if category browser is used show all possible categories
?>
</table>
<?php
//		else
//			print "<br>";
    }

    // draw list header, called if list is not empty
    public function DrawHeader()
    {
        if ($this->showTopNav) {
            $this->drawPageDetails("bottom", true);
        }

        if (isset($this->InplaceForm->Error)) {
            echo "<div class=formerror>{$this->InplaceForm->Error}<br><br></div>";
        } ?>
<table cellspacing="0" cellpadding="3" border="0"<?php echo $this->tableParams; ?>>
<thead>
<?php
        $this->drawFieldHeadersRow();

        if ($this->ShowFilters) {
            $this->DrawFilters();
        }
        echo "<form method=post name=list_{$this->Table}>";

        if (isset($this->InplaceForm)) {
            echo "<input type=hidden name=DisableFormScriptChecks value=0>";
        }
        echo "<input type=hidden name=action>\n";
        echo "<input type='hidden' name='FormToken' value='" . GetFormToken() . "'>\n";

        if ($this->TopButtons) {
            echo "<tr><td colspan=" . (count($this->Fields) + 2) . ">";
            $this->DrawButtons();
            echo "</td></tr>";
        }
        echo "</thead><tbody>";
    }

    // show footer of non-empty list
    public function DrawFooter()
    {
        echo "</tbody></table>\n";

        if (!$this->ReadOnly && $this->ShowEditors) {
            echo "<table width=100% border=0 cellspacing=0 cellpadding=0 class=listFooter>
				<tr><td align=right height='35'>";
            echo "<script src=/lib/scripts/listScripts.js></script>\n";
            $this->DrawButtons();
            echo "</table>";
        }
        echo "</form>";

        if (isset($this->InplaceForm)) {
            echo $this->InplaceForm->CheckScripts();
        }
        $this->drawPageDetails("top", false);
    }

    public function CreateInplaceField($sField, $arField, $arRow, $arFormFields, &$arFields)
    {
        if ($arField["InplaceEdit"] && isset($arFormFields[$sField])) {
            $sFieldName = $sField . "_" . $arRow[$this->KeyField];

            if ($_SERVER['REQUEST_METHOD'] === 'POST' && !array_key_exists($sFieldName, $_POST)) {
                // row was added while user is editing, no data in POST
                // prevent clearing form values
                $_POST[$sFieldName] = $arRow[$sField];
            }
            $arFields[$sFieldName] = $arFormFields[$sField];
            $arFields[$sFieldName]["Value"] = $arRow[$sField];
            $arFields[$sFieldName]["FieldName"] = $sField;
            $arFields[$sFieldName]["KeyField"] = $arRow[$this->KeyField];
        }
    }

    public function CreateInplaceForm($sSQL)
    {
        $q = $this->createQuery($sSQL);
        $arFields = [];
        $arFormFields = $this->Schema->GetInplaceFormFields();

        while (($this->UsePages && !$q->EndOfPage()) || (!$this->UsePages && !$q->EOF)) {
            foreach ($this->Fields as $sField => $arField) {
                $this->CreateInplaceField($sField, $arField, $q->Fields, $arFormFields, $arFields);
            }
            $q->Next();
        }
        $formClass = $this->InplaceFormClass;
        $this->InplaceForm = new $formClass($arFields);
        $this->InplaceForm->KeyField = $this->KeyField;
        $this->InplaceForm->FormName = "list_{$this->Table}";
        // load managers
        foreach ($arFields as $sField => &$arField) {
            if (isset($arField["Manager"])) {
                $this->InplaceForm->ID = $arField["KeyField"];
                $arField["Manager"]->SetFieldValue([]);
            }
        }

        if ($this->InplaceForm->IsPost && ($_POST['action'] == 'update')) {
            if ($this->InplaceForm->Check()) {
                $this->SaveInplaceForm($sSQL);
            }
        }
    }

    public function OpenQuery()
    {
        if ($this->ShowFilters && !isset($this->FilterForm)) {
            $this->CreateFilterForm();
        }
        // open query
        $sSQL = $this->AddFilters($this->SQL);
        // group by
        if (isset($this->groupBy)) {
            $sSQL .= " group by {$this->groupBy}";
        }
        $sSQL .= $this->GetOrderBy();
        // Page Size
        $pageSize = (int) $this->request->query->get("PageSize");

        if (isset($this->PageSizes[$pageSize])) {
            $this->PageSize = $pageSize;
        } elseif (!isset($this->PageSize)) {
            if (isset($this->PageSizes["50"])) {
                $this->PageSize = 50;
            } else {
                $this->PageSize = array_shift(array_keys($this->PageSizes));
            }
        }
        // limit
        if (isset($this->Limit) || ($this->UsePages && !$this->request->query->has('PageBy' . $this->KeyField))) {
            $nPageLimit = (max(intval($this->request->query->get("Page", 1)), 1) + 5) * $this->PageSize;

            if (isset($this->Limit)) {
                $nLimit = $this->Limit;
            } else {
                $nLimit = $nPageLimit;
            }

            if ($nLimit > $nPageLimit) {
                $nLimit = $nPageLimit;
            }
            $sSQL .= " limit {$nLimit}";
        }

        if ($this->InplaceEdit) {
            $this->CreateInplaceForm($sSQL);
        }
        $this->Query = $this->createQuery($sSQL);
        $objRS = &$this->Query;

        if ($this->ShowTotals) {
            $this->Totals = TotalPageNavigator($objRS);
        }

        if (!$objRS->EOF && $this->UsePages) {
            $this->PageNavigator = $objRS->PageNavigator();
        }
        // create url parameters
        $arQS = array_filter($this->request->query->all(), function($value) { return $value !== ''; });

        foreach ($this->UnwantedURLParams as $sParam) {
            unset($arQS[$sParam]);
        }
        $this->URLParamsString = ImplodeAssoc("=", "&", $arQS, true);

        if ($this->URLParamsString != "") {
            $this->URLParamsString = "&" . $this->URLParamsString;
        }
    }

    // show list
    public function Draw()
    {
        $this->ColCount = count($this->Fields);

        if (!$this->ReadOnly) {
            $this->ColCount++;
        }

        if ($this->MultiEdit) {
            $this->ColCount++;
        }
        $this->OpenQuery();
        $objRS = &$this->Query;
        $this->RowCount = 0;
        // filters
        if (!$objRS->EOF) {
            $this->DrawHeader();

            while (($this->UsePages && !$objRS->EndOfPage()) || (!$this->UsePages && !$objRS->EOF)) {
                $this->FormatFields();

                if ($this->repeatHeadersEveryNthRow && ($objRS->Position % $this->repeatHeadersEveryNthRow) === 0) {
                    $this->drawFieldHeadersRow();
                }
                $this->DrawRow();
                $this->RowCount++;
                $objRS->Next();
            }
            $this->DrawFooter();
        } else {
            $this->DrawEmptyList();
        }
    }

    // draw empty list
    public function DrawEmptyList()
    {
        global $Interface;

        if ($this->ShowFilters) {
            $this->DrawHeader();
        }

        if (isset($this->EmptyListMessage)) {
            echo "<p>" . $this->EmptyListMessage . "</p>";
        }

        if (!$this->ReadOnly && $this->ShowEditors) {
            echo "<div align='center'><br>\n";
            echo "<script src=/lib/scripts/listScripts.js></script>\n";
            $this->DrawButtons();
            echo "</div>\n";
        }
    }

    public function DrawButtonsInternal()
    {
        $triggers = [];

        if (!$this->Query->IsEmpty && $this->MultiEdit) {
            //			echo "<input type='Checkbox' onclick=\"javascript:selectAll(this)\">";
            echo "<input type=checkbox value=\"1\" onclick=\"selectCheckBoxes( this.form, 'sel', this.checked )\"> Select All (" . $this->RowCount . ")";
            echo "</td><td align='right' style='border: none;'>";

            if ($this->InplaceEdit) {
                echo "<input id=\"saveChangesId\" class='button' type=button value=\"Save changes\" onclick=\"if(CheckForm(this.form)){ this.form.action.value = 'update'; form.submit();}\"> ";
                $triggers[] = ['saveChangesId', 'Save changes'];
            }

            if ($this->AllowDeletes) {
                echo "<input id=\"DeleteId\" class='button' type=button value=\"Delete\" onclick=\"DeleteSelectedFromList( this.form )\"> ";
                $triggers[] = ['DeleteId', 'Delete'];
            }
        }

        if ($this->CanAdd && !$this->ReadOnly) {
            echo "<input id=\"AddNewId\" class='button' type=button value=\"Add New\" onclick=\"location.href = 'edit.php?ID=0{$this->URLParamsString}'\"> ";
            $triggers[] = ['AddNewId', 'Add New'];
        }

        if ($this->ShowExport && (isset($this->Schema) || isset($this->ExportName))) {
            if ($this->Schema->Name == "") {
                DieTrace("Schema name required for export. Did you forget to call TBaseSchema()?");
            }
            echo "<input id=\"ExportId\" class='button' type=button value=\"Export\" onclick=\"location.href = 'export.php?{$_SERVER['QUERY_STRING']}'\"> ";
            $triggers[] = ['ExportId', 'Export'];
        }

        if ($this->ShowImport && !$this->ReadOnly && isset($this->Schema)) {
            echo "<input id=\"ImportId\" class='button' type=button value=\"Import\" onclick=\"location.href = 'import.php?Schema={$this->Schema->Name}'\"> ";
            $triggers[] = ['ImportId', 'Import'];
        }
        $backTo = $this->request->query->get('BackTo');

        if ($this->ShowBack && $backTo !== null) {
            echo "<input id=\"GoBackId\" class='button' type=button value=\"Go Back\" onclick=\"location.href = '" . urlPathAndQuery($backTo) . "'\"> ";
            $triggers[] = ['GoBackId', 'Go Back'];
        }

        return $triggers;
    }

    // draw buttons
    public function DrawButtons($closeTable = true)
    {
        global $Interface;
        echo "<table id=\"listButtons\" cellspacing=0 cellpadding=0 border=0 width='100%'><tr><td style='text-align: left; border: none;'>";
        $triggers = $this->DrawButtonsInternal();

        if ($closeTable) {
            echo "</td></tr></table>";
        }

        if (isset($Interface) && sizeof($triggers) > 0 && !isset($this->isAddedTriggers)) {
            $this->isAddedTriggers = true;
            $trigButtons = [];

            foreach ($triggers as $trigger) {
                $trigButtons[] = '<input class="button" type="button" value="' . $trigger[1] . '" onclick="$(\'#' . $trigger[0] . '\').trigger(\'click\');" />';
            }
            $trigg = implode("", $trigButtons);
            $Interface->FooterScripts[] = "
				$('#extendFixedMenu').append(" . json_encode("<div align=\"right\" style=\"padding: 0 10px 4px 0;\">{$trigg}</div>") . ");
			";
        }
    }

    //return edit links html
    public function GetEditLinks()
    {
        $arFields = &$this->OriginalFields;
        $s = "";

        if (!$this->ReadOnly) {
            $s .= "<a href=edit.php?ID={$arFields[$this->KeyField]}{$this->URLParamsString}>Edit</a>";

            if ($this->AllowDeletes && !$this->MultiEdit) {
                $s .= " | <input type=hidden name=sel{$arFields[$this->KeyField]} value=\"\">\n<a href='#' onclick=\"if(confirm('Are you sure you want to delete this record?')){ form = document.forms['list_{$this->Table}']; form.sel{$arFields[$this->KeyField]}.value='{$arFields[$this->KeyField]}'; form.action.value='delete'; form.submit();} return false;\">Delete</a>";
            }

            if ($this->showCopy) {
                $s .= " | <a href=\"edit.php?ID={$arFields[$this->KeyField]}{$this->URLParamsString}&Copy=1\">Copy</a>";
            }
        }

        return $s;
    }

    //place holder function for drawing left menu content navigation links, used in Photo Gallery only so far..
    public function DrawListLinks($arHiddens)
    {
    }

    protected function getRowColor(): string
    {
        $rowColor = "#FFFCF5";

        if (($this->Query->Position % 2) == 0) {
            $rowColor = "#F5F2EB";
        }

        return $rowColor;
    }

    private function drawFieldHeadersRow()
    {
        if ($this->Query->Position <= 1) {
            $classes = "";
        } else {
            $classes = " class='repeated-header'";
        }
        echo "<tr bgcolor=\"{$this->headerColor}\"{$classes}>";

        if (!$this->ReadOnly && $this->MultiEdit) {
            echo "  <td class=white width=1%>Select</td>\n";
        }
        $this->DrawFieldHeaders();

        if (!$this->ReadOnly || $this->AlwaysShowEditLinks) {
            echo "  <td class=white width=1%>Manage</td>\n";
        }
        echo "</tr>\n";
    }

    private function createQuery($sql)
    {
        $query = new TQuery($sql);
        $query->PageSize = $this->PageSize;

        if ($this->ShowTotals) {
            $this->Totals = TotalPageNavigator($query);
        }

        if (!$query->EOF && $this->UsePages) {
            $query->SelectPageByURL("Page", [$this->KeyField]);
        }

        return $query;
    }
}

?>
