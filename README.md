# megaplan
Это пакет для подключения к АПИ [Мегаплан](https://megaplan.ru/)

## Установка
С помощью composer <br />
`composer require zloykolobok/megaplan` <br />

## Подключение
В app.php в секции Package Service Providers <br />
`Zloykolobok\Megaplan\MegaplanServiceProvider::class,` <br />

## Конфигурация
Выполняем команду <br />
`php artisan vendor:publish` <br />
Затем вбрыть провайдера Zloykolobok\Megaplan\MegaplanServiceProvider
Будет создан конфигурационный файл config/megaplan.php, где:
* api - если false, то подключаемся через приложение мегаплана
* host - указываем адрес
* login - указываем логи, если подключаемся через API
* password - указываем пароль, если подключаемся чере API
* accessId - указываем UUID приложения, если подключаемся через приложение
* secretKey - указываем токен приложения, если подключаемся через приложение
* https - по умолчанию true, через какой протокол работать

## Документация по Мегаплану
<https://dev.megaplan.ru/>

## Пример использования
```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Zloykolobok\Megaplan\Megaplan;

class TestController extends Controller
{
    public function test()
    {
        $mega = new Megaplan;

        dd($mega->getSchemes());
    }
}
```

## Автор
[Блог автора](https://web-programming.com.ua) <br />
[Задать вопрос](https://web-programming.com.ua/obratnaya-svyaz/)
