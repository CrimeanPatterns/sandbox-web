<?

// -----------------------------------------------------------------------
// mysql console
//		it's recommened to remove this file from production site
// Author: Vladimir Silantyev, ITlogy LLC, vs@kama.ru, www.ITlogy.com
// -----------------------------------------------------------------------

$sPageTitle = "SQL";

include( "../../../kernel/public.php" );
$sTitle = "mysql Console";
$bSecuredPage = False;

$needExport = isset($_POST['format']) && ('' !== $_POST['format']);

if ($needExport) {
    ob_start();
}
require( "$sPath/lib/admin/design/header.php" );
?>
<table border=0 cellpadding=2 cellspacing=1>
  <tr>
    <td>
<?

// Init
if ( !isset( $_POST["SQL"] ) )
  $sSQL = "";
else
  $sSQL = $_POST["SQL"];
if ( !isset( $_POST["Delimiter"] ) )
  $sDelimiter = ";";
else
  $sDelimiter = $_POST["Delimiter"];

//session_write_close();
if (function_exists('getSymfonyContainer')) {
    $databaseName = getSymfonyContainer()->get("database_connection")->getDatabase();
} else {
    $databaseName = $Connection->Parameters['Database'];
}
$_start = (int) (microtime(true) * 10000);
$Connection->Execute('SET SESSION group_concat_max_len = 8192');
$query = new TQuery("
    SELECT
        TABLE_NAME,
        GROUP_CONCAT(column_name SEPARATOR ',') AS TABLE_COLUMNS
    FROM information_schema.columns
    WHERE
        table_schema = '{$databaseName}'
    GROUP BY TABLE_NAME
    ORDER BY TABLE_NAME"
);
$Connection->Execute('SET SESSION group_concat_max_len = 1024');
$_time = ((int) (microtime(true) * 10000) - $_start) / 10000;
//session_start();

$tables = [];
foreach ($query as $column) {
    $tables[$column['TABLE_NAME']] = explode(',', $column['TABLE_COLUMNS']);
}

$selectedFormat = isset($_POST['format']) ? $_POST['format'] : '';
$exportFormats = [
    '',
    'csv',
    'json',
    'json_condensed',
    'json_condensed_column',
];

$isValidFormToken = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $isValidFormToken = isValidFormToken();
}

?>
<link rel="stylesheet" href="../../3dParty/codemirror/lib/codemirror.css">
<link rel="stylesheet" href="../../3dParty/codemirror/addon/hint/show-hint.css">
<link rel="stylesheet" href="../../3dParty/codemirror/addon/lint/lint.css">
<link rel="stylesheet" href="../../3dParty/codemirror/theme/eclipse.css">
<script src="../../3dParty/codemirror/lib/codemirror.js"></script>
<script src="../../3dParty/codemirror/addon/hint/show-hint.js"></script>
<script src="../../3dParty/codemirror/addon/hint/sql-hint.js"></script>
<script src="../../3dParty/codemirror/addon/lint/lint.js"></script>
<script src="../../3dParty/codemirror/mode/sql/sql.js"></script>
<style>
.cm-s-box {
    border: 1px solid black;
    width: 650px;
}
</style>

<form method=post name="sqlConsole">
<input type='hidden' name='FormToken' value='<?=GetFormToken()?>'>

SQL<span style="color: #FFFFFF;">, Schema loaded in <?=$_time?> sec</span><br>
<textarea name=SQL rows=15 cols=100 id="sql"><?=htmlentities($sSQL)?></textarea>
<br>
Ctrl + Space: completion<br/>
Ctrl + Enter: submit<br/>
<br/>
Delimiter <input type=text size=3 name=Delimiter value="<?=$sDelimiter?>">
<br>
Export format
<select name="format">
    <?=implode(array_map(function ($format) use ($selectedFormat) {
        return sprintf('<option name="%s" %s>%s</option>',
            $format,
            $selectedFormat === $format ? 'selected' : '',
            $format
        );
    }, $exportFormats)) ?>
</select>
<br>
<br>
<input type=submit>
</form>
</td>
</tr>
</table>
    <script>
        var textarea = document.getElementById('sql');

        window.sqlEditor = CodeMirror.fromTextArea(textarea, {
            mode: 'text/x-mysql',
            indentWithTabs: true,
            smartIndent: true,
            lineNumbers: true,
            matchBrackets: true,
            autofocus: true,
            theme: "eclipse box",
            extraKeys: {
                "Ctrl-Space": "autocomplete",
                "Ctrl-Enter": function (e) {
                    $('form[name *= "sqlConsole"]').submit();

                    return false;
                },
            },
            hintOptions: {tables: <?=json_encode($tables)?>},
            gutters: ["CodeMirror-lint-markers"],
            lint: true
        });

        $('.CodeMirror').resizable({
            resize: function () {
                window.sqlEditor.setSize($(this).width(), $(this).height());
            }
        });
        mysqlError = null;

        CodeMirror.registerHelper("lint", "sql", function (text) {
            if (!mysqlError) {
                return [];
            }

            var found = [];
            found.push({
                from: CodeMirror.Pos(mysqlError.line - 1, 0),
                to: CodeMirror.Pos(mysqlError.line - 1, 0),
                message: mysqlError.text
            });

            mysqlError = null;

            return found;
        });
     </script>
<?

function exportCSVRow($arValues, $sSeparator)
{
    foreach ($arValues as $nKey => $sValue) {
        $sValue = \str_replace("\"", "\"\"", $sValue);
        $sValue = "\"" . $sValue . "\"";
        $arValues[$nKey] = $sValue;
    }
    echo \implode($sSeparator, $arValues) . "\r\n";
}

function exportJSONRow($arValues, $options = \JSON_PRETTY_PRINT)
{
    echo \json_encode($arValues, $options);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
// Run
if($isValidFormToken){
	$arStatements = explode($sDelimiter, $sSQL);

	foreach ($arStatements as $sStatement) {
		$sStatement = trim($sStatement);
		if ($sStatement != "") {
			$pre = preg_match("/^show\s+create/ims", $sStatement);
            echo "<font color=#0000aa face=\"lucida\"><pre>" . $sStatement . "</pre></font>";
//            session_write_close();
			$startTime = microtime(true);

			if (!$rQuery = $Connection->Execute($sStatement, false)) {
                $needExport = false;
                $mysqlError = $Connection->GetLastError();
                echo "<font color=#ff0000>Error: " . $mysqlError . "<br></font>";

                if (preg_match('/ line (\d+)$/ims', $mysqlError, $matches)) {
                    $errorLine = (int) $matches[1];
                    // add gutter error
                    echo "<script>mysqlError = " . json_encode(['text' => $mysqlError, 'line' => $errorLine]) . "; sqlEditor.setValue(sqlEditor.getValue());</script>";
                }
			} else {
                if (!preg_match('#^(alter|insert|delete|update|set|kill)\b#ims', $sStatement)) {
					// recordset
					$nRowNumber = 0;
					while ($arRow = $Connection->Fetch($rQuery))
					{
						if ($nRowNumber == 0) {
							// table header
                            if (!$needExport) {
                                echo "<table border=1><tr style=\"background: dddddd;\">";
                                foreach ($arRow as $sField => $sValue) {
                                    echo "<td>$sField</td>";
                                }
                                echo "</tr>";
                            } else {
                                ob_end_clean();

                                switch ($selectedFormat) {
                                    case 'csv':
                                        header("Content-type: text/csv; charset=utf-8");
                                        header("Content-Disposition: attachment; filename=console_query.csv");
                                        break;

                                    case 'json':
                                    case 'json_condensed':
                                    case 'json_condensed_column':
                                        header("Content-type: application/json; charset=utf-8");
                                        header("Content-Disposition: attachment; filename=console_query_" . date('Y_m_d_H_i_s') . ".json");
                                        break;
                                }

                                if(isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && ($_SERVER['HTTP_ACCEPT_LANGUAGE'] == 'ru')) {
                                    $sSeparator = ';';
                                } else {
                                    $sSeparator = ',';
                                }

                                switch ($selectedFormat) {
                                    case 'csv':            exportCSVRow(\array_keys($arRow), $sSeparator); break;
                                    case 'json_condensed': echo '['; exportJSONRow(\array_keys($arRow), 0); echo ','; break;
                                    case 'json':           echo '['; break;
                                    case 'json_condensed_column':           echo '['; break;
                                }
                            }

						} else {
                            switch ($selectedFormat) {
                                case 'json_condensed':
                                case 'json_condensed_column':
                                case 'json' : echo ','; break;
                            }
                        }
						// table row
                        if (!$needExport) {
                            echo "<tr>";
                            foreach ($arRow as $sField => $sValue) {
                                if (null === $sValue) {
                                    $sValue = '<span style="color: #a1a7b3">&lt;null&gt;</span>';
                                } elseif ('' === $sValue) {
                                    $sValue = '<span style="color: #a1a7b3">&lt;empty string&gt;</span>';
                                } else {
                                    $sValue = \htmlspecialchars($sValue);
                                }

                                if ($pre)
                                    $sValue = "<pre>$sValue</pre>";
                                echo "<td style='vertical-align: top;'>".$sValue."</td>";
                            }

                            echo "</tr>";
                        } else {
                            switch ($selectedFormat) {
                                case 'csv':                   exportCSVRow($arRow, $sSeparator); break;
                                case 'json_condensed':        exportJSONRow(\array_values($arRow), 0); break;
                                case 'json_condensed_column': echo \json_encode(\current($arRow)); break;
                                case 'json':                  echo "\r\n"; exportJSONRow($arRow); break;
                            }

                        }

						$nRowNumber++;
					}


					$nRowCount = $nRowNumber;
                    if ($nRowNumber > 0 && !$needExport) {
                        echo "</table>";
					    echo "<font color=#00aa00>Rows affected: $nRowCount</font><br>";
                    }

                    if (0 === $nRowCount) {
                        $needExport = false;
                    }

                    if ($needExport) {
                        switch ($selectedFormat) {
                            case 'json_condensed': echo "]"; break;
                            case 'json_condensed_column': echo "]"; break;
                            case 'json' : echo "\r\n]"; break;
                        }
                    }
				}
				else
				{
                    $needExport = false;
					// update, insert, etc.
					$nRowCount = $Connection->GetAffectedRows();
					echo "<font color=#00aa00>Rows affected: $nRowCount</font><br>";
				}
                if (!$needExport) {
                    echo "Time taken: ".round(microtime(true) - $startTime, 3)." sec<br/>";
                }
			}
		}
	}
}
else {
    $needExport = false;
    if (!empty($sSQL)) {
        echo "<font color=#ff0000>CSRF token expired, try again</font>";
    }
}
}
if (!$needExport) {
    require( "$sPath/lib/admin/design/footer.php" );
}
?>
