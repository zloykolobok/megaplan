# megaplan
Это пакет для подключения к АПИ [Мегаплан](https://megaplan.ru/)

## Установка
С помощью composer
composer require zloykolobok/megaplan

## Подключение
В app.php в секции Package Service Providers 
Zloykolobok\Megaplan\MegaplanServiceProvider::class,

## Конфигурация
Выполняем команду
 php artisan vendor:publish --provider=Zloykolobok\Megaplan\MegaplanServiceProvider
Будет создан конфигурационный файл config/megaplan.php, где:
* api - если false, то подключаемся через приложение мегаплана
* host - указываем адрес
* login - указываем логи, если подключаемся через API
* password - указываем пароль, если подключаемся чере API
* accessId - указываем UUID приложения, если подключаемся через приложение
* secretKey - указываем токен приложения, если подключаемся через приложение

## Документация по Мегаплану
<https://dev.megaplan.ru/>

## Автор
[Блог автора] <https://web-programming.com.ua>
[Задать вопрос] <https://web-programming.com.ua/obratnaya-svyaz/>



