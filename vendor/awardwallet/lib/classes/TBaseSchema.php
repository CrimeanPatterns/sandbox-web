<?php

class TBaseSchema
{
    public $TableName;
    public $KeyField;
    public $Fields;
    public $DefaultSort;
    public $ListClass = "TBaseList";
    public $FormClass = false;
    public $ShowMethod = "Draw";
    public $bIncludeList = true;
    public $Admin = false;
    public $Description;
    public $Name;
    public $FilterFields = [];
    public $id = 0;

    private $tables = [];
    private array $formTuners = [];

    public function __construct()
    {
        $this->TBaseSchema();
    }

    /**
     * @deprecated use __construct
     */
    public function TBaseSchema()
    {
        if (isset($_GET["ID"])) {
            $this->id = intval($_GET["ID"]);
        }
        $this->Name = self::getSchemaName(get_class($this));
        $this->TableName = $this->Name;

        $this->tables = array_map(
            function(array $row) {
                return array_pop($row);
            },
            iterator_to_array(new TQuery("show tables"))
        );

        $this->detectFields();

        if (isset($this->Fields['SortIndex'])) {
            $this->DefaultSort = 'SortIndex';
        }

        $listClass = self::getListClass(get_class($this));

        if (class_exists($listClass)) {
            $this->bIncludeList = false;
            $this->ListClass = $listClass;
        }
    }

    public static function getSchemaName(string $className): string
    {
        $className = preg_replace('#Schema$#ims', '', $className);

        $lastSlashPos = strrpos($className, '\\');

        if ($lastSlashPos !== false) {
            return substr($className, $lastSlashPos + 1);
        }

        if ('T' === $className[0] && ctype_upper(substr($className, 0, 2))) {
            return substr($className, 1);
        }

        return $className;
    }

    public static function getListClass(string $schemaClass): string
    {
        return preg_replace('#Schema$#ims', '', $schemaClass) . 'List';
    }

    public function CompleteFields()
    {
        global $Connection;

        if (!isset($this->KeyField)) {
            $this->KeyField = $Connection->PrimaryKeyField($this->TableName);
        }
    }

    public function GetListFields()
    {
        $arFields = $this->Fields;

        if ($this->Admin && !isset($arFields[$this->KeyField])) {
            $arFields = [
                $this->KeyField => [
                    "Type" => "integer",
                    "Caption" => "ID",
                ],
            ] + $arFields;
        }

        foreach ($arFields as $sField => &$arField) {
            if (!isset($arField["Sort"]) && ($arField["Database"] ?? true) && in_array($arField["Type"],
                    ["date", "datetime", "integer", "float"])) {
                $arField["Sort"] = "$sField DESC";
            }
        }
        unset($arField);

        return $arFields;
    }

    public function GetFormFields()
    {
        $result = $this->Fields;
        unset($result[$this->TableName . "ID"]);

        return $result;
    }

    public function GetInplaceFormFields()
    {
        return $this->GetFormFields();
    }

    /**
     * create editor form.
     *
     * @return TBaseForm|TForm
     */
    public function CreateForm()
    {
        require_once __DIR__ . "/TBaseFormEngConstants.php";

        if (class_exists("TForm")) {
            $objForm = new TForm($this->GetFormFields(), false);
        } else {
            $objForm = new TBaseForm($this->GetFormFields(), false);
        }

        if ($this->FormClass) {
            $objForm = new $this->FormClass($this->GetFormFields(), false);
        }
        $objForm->TableName = $this->TableName;
        $this->TuneForm($objForm);
        $objForm->CompleteFields();

        return $objForm;
    }

    public function TuneForm(TBaseForm $form)
    {
        if (count($form->Uniques) === 0) {
            $form->Uniques = $this->detectUniques();
        }

        foreach ($this->FilterFields as $sField) {
            if (ArrayVal($_GET, $sField) != "") {
                $form->Filters[$sField] = intval($_GET[$sField]);
            }
        }

        if (isset($form->Fields['SortIndex']) && $this->id == 0) {
            $form->Fields['SortIndex']['Value'] = $this->getNextSortIndexValue();
        }

        foreach ($this->formTuners as $tuner) {
            call_user_func($tuner, $form);
        }
    }

    /**
     * create List object for this schema.
     *
     * @param array() $arFields
     *
     * @return TBaseList
     */
    public function CreateList($arFields = null)
    {
        if (!isset($arFields)) {
            $arFields = $this->GetListFields();
        }

        if (!isset($this->DefaultSort)) {
            foreach ($arFields as $sField => $arField) {
                if (ArrayVal($arField, "Sort", true) && ArrayVal($arField, "Database", true)) {
                    $this->DefaultSort = $sField;

                    break;
                }
            }
        }
        $sListClass = $this->ListClass;
        $objList = new $sListClass($this->TableName, $arFields, $this->DefaultSort);
        $this->TuneList($objList);

        return $objList;
    }

    /**
     * fine-tune created list object.
     *
     * @param  $list TBaseList
     *
     * @return void
     */
    public function TuneList(&$list)
    {
        $list->ReadOnly = false;
        $list->CanAdd = true;
        $list->ShowEditors = true;
        $list->AllowDeletes = true;
        $list->ShowFilters = true;
        $list->MultiEdit = true;
        $list->UsePages = true;
        $list->Schema = &$this;

        if ($this->Admin) {
            $list->ExternalDelete = "delete.php";
        }

        foreach ($this->FilterFields as $sField) {
            if (ArrayVal($_GET, $sField) != "") {
                $list->Filters[$sField] = "$sField = " . intval($_GET[$sField]);
            }
        }

        $list->PageSizes = ["50" => "50", "100" => "100", "500" => "500", "1000" => "1000"];
        $list->PageSize = 1000;
    }

    public function ShowList()
    {
        $objList = $this->CreateList();
        $objList->Update();
        $sMethod = $this->ShowMethod;
        $objList->$sMethod();
    }

    public function ExportTXT()
    {
        $objList = $this->CreateList($this->GetExportFields());
        $objList->ExportTXT();
    }

    public function ExportCSV()
    {
        $objList = $this->CreateList($this->GetExportFields());
        $objList->ExportCSV();
    }

    public function GetExportFields()
    {
        $arFields = [];

        foreach ($this->Fields as $sField => $arField) {
            if (ArrayVal($arField, 'ExportCSV', false) || (!isset($arField["Manager"]) && ArrayVal($arField, "Database", true))) {
                $arFields[$sField] = $arField;
            }
        }

        return $arFields;
    }

    public function ShowForm()
    {
        $objForm = $this->CreateForm();
        $objForm->Edit();
    }

    public function DrawDescription()
    {
        if (is_array($this->Description)) {
            echo "<table cellspacing='0' cellpadding='0' border='0' align='center'><tr>";

            foreach ($this->Description as $key => $value) {
                echo "<td nowrap>" . $value . "</td>";

                if ($key != count($this->Description) - 1) {
                    echo "<td width='20' align='center' style='padding-top: 2px;'><img src='/lib/images/arrowRight3.gif'></td>";
                }
            }
            echo "</tr></table>";
        }
    }

    public function GetImportKeyFields()
    {
        return [$this->KeyField];
    }

    /**
     * @param callable $tuner - function(TBaseForm $form) : void. Will be called for each created form
     */
    public function addFormTuner(Callable $tuner)
    {
        $this->formTuners[] = $tuner;
    }

    protected function guessFieldOptions(string $fieldName, array $fieldInfo): ?array
    {
        if ($fieldInfo['LookupTable'] === null) {
            return null;
        }

        $fields = [];

        foreach (new TQuery("describe {$fieldInfo['LookupTable']}") as $row) {
            $fields[] = $row['Field'];
        }

        foreach (['DisplayName', 'Name', 'Code'] as $lookupField) {
            if (!in_array($lookupField, $fields)) {
                continue;
            }

            $sortField = $lookupField;

            if (isset($fields['SortIndex'])) {
                $sortField = 'SortIndex';
            }

            $options = SQLToArray("select {$fieldInfo['LookupTable']}ID as ID, {$lookupField} as Name from {$fieldInfo['LookupTable']} order by {$sortField} limit 1000",
                "ID", "Name");
            // @TODO: cache this decision
            if (count($options) > 1000) {
                continue;
            }

            if (!$fieldInfo['Required']) {
                $options = ["" => ""] + $options;
            }

            return $options;
        }

        return null;
    }

    private function detectFields(): void
    {
        $fields = [];

        try {
            foreach (new TQuery("describe `{$this->TableName}`") as $row) {
                if (in_array($row['Field'], ['PictureVer', 'PictureExt'])) {
                    continue;
                }

                $field = [
                    'Type' => $this->extractFieldType($row['Type'], $row['Field']),
                    'Size' => $this->extractFieldSize($row['Type']),
                    'Required' => $row['Null'] === 'NO',
                    'LookupTable' => $this->detectLookupTable($row['Field']),
                ];

                if ($row['Extra'] === 'auto_increment') {
                    $field['Caption'] = 'id';
                    $field['filterWidth'] = 30;
                    $field['InputAttributes'] = 'readonly';
                }

                if ($row['Default'] !== null) {
                    $field['Value'] = $row['Default'];
                }

                $options = $this->guessFieldOptions($row['Field'], $field);

                if ($options !== null) {
                    $field["Options"] = $options;
                }

                if (in_array($field['Type'], ['integer', 'float'])) {
                    $field['filterWidth'] = 60;
                }

                $fields[$row['Field']] = $field;
            }
        } catch (\Doctrine\DBAL\Exception\TableNotFoundException $exception) {
            return;
        }

        $this->Fields = $fields;
    }

    private function extractFieldType(string $type, string $name): string
    {
        if (strpos($type, 'int') !== false) {
            if (strpos($name, 'Is') === 0 || strpos($name, 'Visible') === 0) {
                return 'boolean';
            }

            return 'integer';
        }

        if (strpos($type, 'double') !== false || strpos($type, 'decimal') !== false) {
            return 'float';
        }

        if (strpos($type, 'date') !== false) {
            return 'date';
        }

        return 'string';
    }

    private function extractFieldSize(string $type): ?int
    {
        if (strpos($type, 'char') !== false && preg_match('#\((\d+)\)#ims', $type, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function getNextSortIndexValue(): ?int
    {
        $q = new TQuery("select SortIndex from {$this->TableName} order by SortIndex desc limit 1");

        if (!$q->EOF) {
            return round(($q->Fields["SortIndex"] + 10) / 10) * 10;
        }

        return 10;
    }

    private function detectUniques(): array
    {
        $uniques = [];

        foreach (new TQuery("show indexes from {$this->TableName}") as $row) {
            if ($row['Non_unique'] !== '0' || $row['Key_name'] === 'PRIMARY') {
                continue;
            }

            if (!isset($uniques[$row['Key_name']])) {
                $uniques[$row['Key_name']] = ['Fields' => [$row['Column_name']], 'AllowNulls' => true];
            } else {
                $uniques[$row['Key_name']]['Fields'][] = $row['Column_name'];
            }
        }

        return array_values($uniques);
    }

    private function detectLookupTable(string $field) : ?string
    {
        if (!preg_match('#(\w+)ID$#', $field, $matches) || $field === "{$this->TableName}ID") {
            return null;
        }

        $lookupTable = $matches[1];

        if (!in_array($lookupTable, $this->tables)) {
            return null;
        }

        return $lookupTable;
    }
}
