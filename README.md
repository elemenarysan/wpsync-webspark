# Описание 
Плагин  wpsync-webspark , плагин для WordPress + WooCommerce

Дергает даннные о продуктов из апи и закидывает их базу, чистит отсутствющие

# Требования

- Linux : Debian 10+ / CentOS 7+
- MySql 5.6+ / MariaDb
- php 7.2+
- apache 2.4+ / nginx 1.12+
- wordpress 5.6+
- WooCommerce 6.2+


# Предустановка


- Установить ОС / Хост
- Поднять базу
- Настроить Web сервер
- Установить WordPress
- Установить и активировать WooCommerce


## Установить wp-cli
В корне проекта
```
 curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
 chmod +x wp-cli.phar
 php wp-cli.phar --info
```

# Установка плагина
## Скачивание и активация
В папке плагинов (wp-content/plugins) выполнить
```
git clone https://github.com/elemenarysan/wpsync-webspark.git
```
В кабинете админа - плагины (/wp-admin/plugins.php) активировать плагин wpsync-webspark

## Настройка
В файле настноек wp-config.php прописать опцию
```
define( 'WC_PRODUCTS_IMPORT_URL', 'api_url_to_data' );
```

# работа с плагином
## Запуск импорта из командной строки
Из корня проекта

Справка / перечень команд
```
 php wp-cli.phar products
```

начать импорт, процесс значительно продолжителен
```
 php wp-cli.phar products importStart
```

Остановить импорт
```
 php wp-cli.phar products importStop
```

Проверить статус импорта
```
 php wp-cli.phar products importCheck
```

Посмотреть лог
```
 cat wp-content/plugins/wpsync-webspark/import.log
```

## Веб интерфейс
Кабинет админа - Товары - Импорт товаров (/wp-admin/edit.php?post_type=product&page=wc-product-import)

- Ссылка Начать , чтоб начать 
- Ссылка Остановить , чтоб остановить
- Ссылка Лог импорта , чтоб открыть лог импорта

