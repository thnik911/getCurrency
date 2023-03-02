# getCurrency
Данный скрипт позволяет получать валюту с сайта ЦБ РФ и редактировать справочник валюты в Битрикс24 для корректного расчета сумм сделок в отображении канбаном и в отчетах.

Скрипт автоматически запускается по крону ежедневно в 12:00. Результатом выполнения скрипта является заполнение справочника валют, а также добавление элементов в УС для хранения данных

**Механизм работы**:

1. Как было сказано раннее, скрипт сам запускается по крону и делает запрос к ЦБ РФ. ЦБ возвращает xml.
2. На основании xml начинаем искать интересующие нас валюты (обращаю внимание, что валюты в xml идут в алфавитном порядке).
3. Далее на основании полученных котировок обновляем валюту внутри Битрикс24 и сохраняем в УС (универсальный список) для хранения данных.

Решение может работать как на облачных, так и коробочных Битрикс24. 

**Как запустить**:
1. getCurrency.php и auth.php необходимо разместить на хостинге с поддержкой SSL.
2. В разделе "Разработчикам" необходимо создать входящий вебхук с правами на CRM (crm) и Списки (lists). Подробнее как создать входящий / исходящий вебхук: [Ссылки на документацию 1С-Битрикс](https://github.com/thnik911/getCurrency/blob/main/README.md#%D1%81%D1%81%D1%8B%D0%BB%D0%BA%D0%B8-%D0%BD%D0%B0-%D0%B4%D0%BE%D0%BA%D1%83%D0%BC%D0%B5%D0%BD%D1%82%D0%B0%D1%86%D0%B8%D1%8E-1%D1%81-%D0%B1%D0%B8%D1%82%D1%80%D0%B8%D0%BA%D1%81).
3. Полученный "Вебхук для вызова rest api" прописать в auth.php.
4. В массивах по созданию элементов УС необходимо указать ID списка в 'IBLOCK_ID', а для подмассива 'FIELDS' необходимо указать Ваши коды полей.
5. Если Вам необходимо получить другую валюту, то измените значение в условии if / elseif. К примеру, в строке 21 замените EUR на AUD. Посмотреть все коды валют: [Ссылки на документацию 1С-Битрикс](https://github.com/thnik911/getCurrency/blob/main/README.md#%D1%81%D1%81%D1%8B%D0%BB%D0%BA%D0%B8-%D0%BD%D0%B0-%D0%B4%D0%BE%D0%BA%D1%83%D0%BC%D0%B5%D0%BD%D1%82%D0%B0%D1%86%D0%B8%D1%8E-1%D1%81-%D0%B1%D0%B8%D1%82%D1%80%D0%B8%D0%BA%D1%81). Не забудьте также указать актуальный ID для метода 'crm.currency.update'.
6. Добавить в крон выполнение скрипта по расписанию. К примеру, вот так: 
0 12 * * * root /usr/bin/php /home/bitrix/www/local/webhooks/startWorkflow.php >/dev/null 2>&1

### Ссылки на документацию 1С-Битрикс и ЦБ РФ

<details><summary>Развернуть список</summary>

1. Как создать Webhook https://dev.1c-bitrix.ru/learning/course/index.php?COURSE_ID=99&LESSON_ID=8581&LESSON_PATH=8771.8583.8581
2. Справочник по кодам валют с сайта ЦБ РФ: https://cbr.ru/scripts/XML_daily.asp?date_req=02/03/2002
</details>
