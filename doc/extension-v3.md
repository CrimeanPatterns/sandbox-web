Extension V3 - написание парсеров
-----------------------------------

Стартовая страница для написания парсеров:
http://parserbox-web.awardwallet.docker:38401/admin/debug-extension

Если расширение браузера еще не установлено - будет показана иструкция по установке.

Обратите внимание, что домен должен быть parserbox-web.awardwallet.docker. 
На другом домене работать не будет, пропишите в hosts, согласно [инструкции](../README.md#hosts).

Парсер создается в папке провайдера (src/engine/providercode) как файл с названием ProvidercodeExtension.php, содержащий класс ProvidercodeExtension.

Должен реализовывать интерфейсы LoginWithIdInterface и ParseInterface.
Если провайдер делает только автологин, без сбора данных - достаточно LoginWithIdInterface

Пример:
[src/engine/etihad/EtihadExtension.php](https://github.com/AwardWallet/engine/blob/master/etihad/EtihadExtension.php)
```php
class EtihadExtension extends AbstractParser implements LoginWithIdInterface, ParseInterface, ParseItinerariesInterface, ParseHistoryInterface {
    // реализуйте методы интерфейсов
}
```
Далее нужно реализовать методы описанные в [LoginWithIdInterface](https://github.com/AwardWallet/extension-worker/blob/master/src/LoginWithIdInterface.php) 
и [ParseInterface](https://github.com/AwardWallet/extension-worker/blob/master/src/ParseInterface.php)

Общая логика работы парсера:

1. **getStartingUrl()**
2. **isLoggedIn()** ?
   - Да:
     - **getLoginId()**
     - loginId Совпадает с записанным с прошлой проверки?
       - Да: переходим к **parse()**
       - Нет: **logout()**
       - После logout() происходит автоматический переход по URL указанному в getStartingUrl()
   - Нет: 
     - **login()**
3. **parse()**
4. **parseItineraries()**
5. **parseistory()**

При парсинге собранные данные записывайте в объект $master переданный в метод parse
```php
// Используйте создание Statement один раз
$st = $master->createStatement();
// Name
$st->addProperty('Name', '');
// При необходимости, используйте ранее созданный Statement
$st = $master->getStatement();
```

### Основные методы для сбора данных
**evaluate** - поддерживает XPath запросы c возможностью кликать, вставлять значения и т.д. Возвращает Element.
Ждет наличия элемента на станице и выкидывает исключение ElementNotFoundException при его отсутствии.
```php
$tab->evaluate('//img', EvaluateOptions::new()
    ->nonEmptyString() // Искомый элемент на странице может присутствывать, но текст может загрузиться через время. Эта опция заставит ждать появления текста в элементе
    ->contextNode($root) // Родительский элемент
    ->visible(true) // По умолчанию true, ждет пока элемент не станет видимым
    ->timeout(15) // Время ожидания, через которое запрос упадет по таймауту
);
```

**findText** - поддерживает XPath запросы. Возвращает string. Дает возможность получить текстовую метку из тегов и их атрибутов.
Ждет наличия элемента на станице и выкидывает исключение ElementNotFoundException при его отсутствии
```php 
$tab->findText('//img/@src', FindTextOptions::new() // Тоже что и EvaluateOptions но есть preg
    ->preg('/^([\d.,]+) Miles/') // Регулярные выражения
)     
```
**findTextNullable** - тоже самое что и findText но поумолчанию timeout 0 - это значит, что метод не ждет появления элемента и не выкидывает исключения, если элемент не найден.
```php
$tab->findTextNullable('//img/@src', FindTextOptions::new()->timeout(60))    
```


**evaluateAll, findTextAll** - Могут найти на странице один и более элементов
На данный момент, запросы НЕ ждут появления элемента, поэтому, перед их вызовом следует
использовать evaluate или findText
```php
$tab->evaluateAll() // array Element
$tab->findTextAll() // array String
 
// Логика работы как у $tab->evaluate, но запросы как у document.querySelector в JavaScript
$tab->querySelector('div.user-panel:not(.main) input[name="login"]')
```

### Навигация по сайту
```php
$tab->evaluate('//a[@id="nav"]')->click();
$tab->gotoUrl('http://');
$tab->back();
```

### Выполнение API запросов
https://github.com/JakeChampion/fetch
```php
$options = [
    'method'  => 'post',
    'headers' => [
        'Accept'        => 'application/json, text/plain, */*',
        'Content-Type'  => 'application/json',
        //'Content-Type' => 'application/x-www-form-urlencoded',
    ],
    'body' => json_encode(['param'=>'value']),
    //'body' => http_build_query(['param'=>'value'])
];

$json = $tab->fetch("https://www.site.com/api", $options)->body;
$this->logger->info($json);
$json = json_decode($json);
```
### Выбор регионов

Возможно создавать разные парсеры для разных регионов.

Создайте класс вида 
```php
<?php

namespace AwardWallet\Engine\chase;

use AwardWallet\ExtensionWorker\ParserSelectorInterface;
use AwardWallet\ExtensionWorker\SelectParserRequest;
use Psr\Log\LoggerInterface;

class ChaseExtensionParserSelector implements ParserSelectorInterface
{
    public function selectParser(SelectParserRequest $request, LoggerInterface $logger): string
    {
        if ($request->getLogin2() === 'canada') {
            return ChaseExtensionCanada::class;
        }

        return ChaseExtension::class;
    }
}
```
Будет использован другой парсер для канады.

### Работа с фреймами
```php
$frame = $tab->selectFrameContainingSelector("//input[@name='userId']", SelectFrameOptions::new()->method("evaluate"));
$loginLabel = $frame->querySelector('label#userId-label');
$loginLabel->click();
```
Обратите внимание, что в метод selectFrameContainingSelector передается селектор внутри фрейма, 
сам фрейм указывать в селекторе не надо.
```php
$frame = $tab->selectFrameContainingSelector("//input[@name='userId']", SelectFrameOptions::new()->method("evaluate")); // правильно
$frame = $tab->selectFrameContainingSelector("//iframe//input[@name='userId']", SelectFrameOptions::new()->method("evaluate")); // неправильно

```

### Работа с Shadow DOM

Бывает что часть элементов на странице находится в Shadow DOM.
Вы можете увидеть это в инспекторе хрома, если у одного из родителей элемента есть отметка #shadow-root
![shadow-dom](images/shadow-dom.png "Shadow DOM")

Подробнее о shadow DOM:
https://developer.mozilla.org/en-US/docs/Web/API/Web_components/Using_shadow_DOM

Такие элементы нельзя найти через querySelector или evaluate.
Их надо искать через свойство shadowRoot у элемента, в котором они находятся.
Пример:
```php
// нашли элемент на котором висит отметка #shadow-root
$loginInputShadowRoot = $tab->querySelector("mds-text-input")->shadowRoot();
// выполняем селекторы внутри этого элемента
$loginInput = $loginInputShadowRoot->querySelector("input");
$loginInput->setValue($credentials->getLogin());
```

### Показ сообщений

```php
$tab->showMessage(Tab::MESSAGE_RECAPTCHA);
```

### Парсинг всех данных в одном методе

Иногда трудно разделить сбор истории, резерваций и баланса на методы parse, parseHistory, parseItineraries.
В этом случае можно использовать интерфейс parseAll. Пример такого парсера:

```php
namespace AwardWallet\Engine\testprovider;

use AwardWallet\ExtensionWorker\AbstractParser;
use AwardWallet\ExtensionWorker\AccountOptions;
use AwardWallet\ExtensionWorker\Credentials;
use AwardWallet\ExtensionWorker\LoginResult;
use AwardWallet\ExtensionWorker\LoginWithIdInterface;
use AwardWallet\ExtensionWorker\ParseAllInterface;
use AwardWallet\ExtensionWorker\ParseHistoryOptions;
use AwardWallet\ExtensionWorker\ParseItinerariesOptions;
use AwardWallet\ExtensionWorker\Tab;
use AwardWallet\Schema\Parser\Component\Master;

class TestproviderExtension extends AbstractParser implements LoginWithIdInterface, ParseAllInterface
{

    public function getStartingUrl(AccountOptions $options): string
    {
        return 'https://yandex.ru';
    }

    public function isLoggedIn(Tab $tab): bool
    {
        // TODO: Implement isLoggedIn() method.
        return false;
    }

    public function getLoginId(Tab $tab): string
    {
        // TODO: Implement getLoginId() method.
        return '';
    }

    public function logout(Tab $tab): void
    {
        // TODO: Implement logout() method.
    }

    public function login(Tab $tab, Credentials $credentials): LoginResult
    {
        return LoginResult::success();
    }

    public function parseAll(Tab $tab, Master $master, AccountOptions $accountOptions, ?ParseHistoryOptions $historyOptions, ?ParseItinerariesOptions $itinerariesOptions): void
    {
        // собираем все данные в одном методе
        $master->createStatement()->setBalance(100);
        $master->createFlight()->addConfirmationNumber('123');
        $master->getStatement()->addActivityRow(['PostingDate' => '2021-01-01', 'Description' => 'Test', 'Amount' => 100]);
    }
}
```

Параметры $historyOptions и $itinerariesOptions могут быть null - это означает что парсинг
истории/резерваций запускать не надо.

### Управление фокусом вкладки

Вкладка открываемая для автологина активна всегда.

Когда для парсинга открывается новая вкладка - она по умолчанию неактивна.

Некоторые провайдеры не загружаются в фоновой вкладке, или требуют решения капчи.
В этом случае вы можете сделать открываемую вкладку активной реализовав интерфейс ActiveTabInterface:

```php
class ChaseExtension extends AbstractParser implements ..., ActiveTabInterface

...

    public function isActiveTab(AccountOptions $options): bool
    {
        return true;
    }

}
```

### Выброс стандартных ошибок ###

```php
throw new NotAMemberException();
throw new ProfileUpdateException();
throw new AcceptTermsException();
 
```

### Автологин в резервацию ###

Автололгин пользователя на страницу резервации используя поля вида RecordLocator, First Name, Last Name.
Реализуйте интерфейс LoginWithConfNoInterface:

```php
class ChaseExtension extends AbstractParser implements ... LoginWithConfNoInterface
{
    public function getLoginWithConfNoStartingUrl(array $confNoFields): string
    {
        return 'https://yandex.ru';
    }

    public function loginWithConfNo(Tab $tab, array $confNoFields): bool
    {
        $this->logger->info("loginWithConfNo", $confNoFields);

        return true;
    }
}
```

Массив confNoFields приходит в виде:
```php
[
    'RecordLocator' => '123456',
    'FirstName' => 'John',
    'LastName' => 'Doe',
]
```