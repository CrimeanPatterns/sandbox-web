# Парсинг истории

История - это то, как пользователь получал и расходовал свои бонусные баллы. 
На сайте awardwallet показана в попапе подробной информации об аккаунте, 
на закладке History. Для того чтобы увидеть историю нужно быть AW+ 

Каждый сайт провайдера имеет свой формат истории, как правило это таблица с разным набором колонок.

## Когда мы собираем историю

История собирается при каждой проверке аккаунта и возвращает транзакции, отталкиваясь от даты startDate.

Для сбора история в парсере предназначены три метода:

## GetHistoryColumns

```php
function GetHistoryColumns() {
    return [
        "Type"                    => "Info",
        "Eligible Nights"         => "Info",
        "Post Date"               => "PostingDate",
        "Description"             => "Description",
        "Starpoints"              => "Miles",
        "Bonus"                   => "Bonus",
        "Transaction Description" => "Info",
    ];
}
```

Этот метод возвращает описание колонок, в формате "Название колонки" => "Тип колонки".

Поля "Info" по умолчанию имеют строковый тип, но возможна и строгая типизация date, integer, decimal. Это нужно указывать также в текущем методе 
```php
function GetHistoryColumns() {
    return [
        "Transaction Description" => "Info",
        "Eligible Nights"         => "Info.Int",
        "Transaction Date"        => "Info.Date",
        "Somepoints"              => "Info.Decimal",
    ];
}
```

Важно: при проверке через лоялти GetHistoryColumns должен быть в файле класса, который собирает историю. Для отображения вкладки с историей на фронте и при проверке через wsdl GetHistoryColumns *всегда* должен быть еще и в functions.php

## GetHiddenHistoryColumns

```php
function GetHiddenHistoryColumns() {
    return [
        'Transaction Description'
    ];
}
```

Этот метод определяет какие колонки будут скрыты от глаз пользователя, но при этом будут сохраняться в базу.

### Возможные типы колонок

Все типы колонок кроме Info должны встречаться только один раз. То есть не может быть например две колонки типа PostingDate.

Тип              | Обязательная | Тип            | Описание
---------------- | ------------ | -------------- | -------------
PostingDate      | Да           | unix timestamp | дата транзакции
Miles            | Нет          | float          | число миль, может быть отрицательным
Bonus            | Нет          | string          | бонусные мили, часто представлены на сайте отдельной колонкой, мы специально выводим такую колонку как тип Bonus для партнеров, в частности для tripit
Description      | Нет          | string          | описание транзакции
Info             | Нет          | string          | любая дополнительная колонка, может быть более чем одна
Info.Date        | Нет          | unix timestamp  | любая не PostingDate дата, должна переводится в unixtime (по слухах, формат YYYY/MM/DD также валидный) для корректного отображения дат в профиле юзера, может быть более чем одна
Info.Int         | Нет          | integer        | дополнительная колонка с типом int, может быть более чем одна
Info.Decimal     | Нет          | float          | дополнительная колонка с типом float, может быть более чем одна
Amount           | Нет          | float          | Потраченная сумма в валюте; может быть только одна колонка такого типа
AmountBalance    | Нет          | float          | Общий баланс в валюте; может быть только одна колонка такого типа
MilesBalance     | Нет          | float          | Общий баланс в милях; может быть только одна колонка такого типа
Currency         | Нет          | integer        | Валюта операции; код валюты из таблицы Currency - может быть только одна колонка такого типа
Category         | Нет          | string          | Категория транзакции: travel, shopping, dining etc.

## ParseHistory

Возвращает массив строк истории. Каждая строка - ассоциативный массив колонок, описанных в GetHistoryColumns. 

```php
public function ParseHistory($startDate = null) {
	return [
		[
			'Post Date' => 32432432443,
			'Description' => 'PURCHASED POINTS-MEMBER SELF',
			'Starpoints' => 200,
			'Bonus' => 500,
			'Eligible Nights' => '-'
		],
		...
	];
}
```
На входе функция получает дату, начиная с которой надо собирать резервации, unixtime. Может быть null.

Если startDate не пустое - то надо собрать только ту историю, дата которой больше или равна startDate.

## Истории SubAccounts (транзакции по кредитным картам)

### Стартовая дата для сбора

В случае со сбором истории основного аккаунта, дату, с которой необходимо собирать данные, можно получить, обратившись к свойству TAccountChecker::HistoryStartDate или к параметру startDate внутри метода TAccountChecker::ParseHistory(). В случае с историей субакка, необходимую стартовую дату возможно получить вызовом метода TAccountChecker::getSubAccountHistoryStartDate(subAccountCode), где subAccountCode - уникалыный код субаккаунта в рамках конкретного аккаунта (ключ 'Code' в массиве сбора субакка).

Парсер может возвращать более старую историю, чем historyStartDate, если strictHistoryStartDate = false

```php
$historyStartDate = $this->HistoryStartDate;

if (!$this->strictHistoryStartDate && $historyStartDate !== null) {
  // парсим на сколько угодно дней назад, необязательно три
  $historyStartDate = strtotime("-4 day", $historyStartDate);
}

// вариант из chase

$startDate = $this->getSubAccountHistoryStartDate($code);
$this->logger->debug('[History start date: ' . ((isset($startDate)) ? date('Y/m/d H:i:s', $startDate) : 'all') . ']');

// refs #19361, note-78
if (!$this->strictHistoryStartDate && $startDate !== null) {
    $startDate = strtotime("-4 day", $startDate);
    $this->logger->debug('[Set history start date -4 days for ' . $code . ': ' . ((isset($startDate)) ? date('Y/m/d H:i:s', $startDate) : 'all') . ']');
}
```

### Сбор данных

Для сбора истории субаккаунта необходимо использовать идентичные для сбора истории основного аккаунта поля, то есть те, которые описаны в методе GetHistoryColumns(). Сам сбор истории субакка необходимо инициировать внутри метода Parse(), оставив ParseHistory(startDate) только для истории основного акка.

Собранную историю необходимо записывать в момент добавления аккаунта методом TAccountChecker::AddSubAccount([...]), передав в массиве параметров ключ HistoryRows с собранной по субакку историей. 

На примере это должно выглядеть примерно так:

```php
public function Parse() {
	...
    $this->AddSubAccount([
        "Balance" => 1,
        "Code" => "SubAcc1",
        "DisplayName" => "SubAccount 1",
        "HistoryRows" => [
            [
                'Post Date' => 1445904000,
                'Type' => 'Bonus',
                'Eligible Nights' => '-',
                'Bonus' => '+2,500',
                'Description' => 'Subacc1 hist 1',
            ],
            [
                'Post Date' => 1439424000,
                'Type' => 'Award',
                'Eligible Nights' => '-',
                'Starpoints' => '-2,500',
                'Description' => 'Subacc1 hist 2',
            ],
            ...
        ],
    ]);
	...
}

```

Реализацию можно подсмотреть в тестовом провайдере или chase:

* [testprovider](https://github.com/AwardWallet/engine/blob/master/testprovider/History/SubAccounts.php)
* [chase](https://github.com/AwardWallet/engine/blob/master/chase/functions.php)

## combineHistoryBonusToMiles

Необязательный метод. По умолчанию возвращает false. Если переопределить его и вернуть true, то включится логика по объединению колонок Bonus и Miles в одну, останется только колонка Miles. 

Нужен для обработки ситуации когда на сайте провайдера нет колонки Bonus, мы ее искусственно синтезируем, для партнеров, но при этом не хотим показывать Bonus как отдельную колонку у себя на сайте.

Пример такого провайдера - airfrance.

# Сбор истории через extension

Пример расположен в engine/testextension/extension.js

Парсер должен вернуть историю в свойстве HistoryRows, в том же формате что в методе ParseHistory.

При этом сами колонки должны быть определены в php парсере, в методе GetHistoryColumns
