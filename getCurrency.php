    <?
ini_set("display_errors", "1");
ini_set("display_startup_errors", "1");
ini_set('error_reporting', E_ALL);

/* AUTH */
require_once 'auth.php';

$dateForB24 = date("d.m.Y");

/*
Запускается по крону ежедневно в 12:00 (по информации ЦБ РФ, именно в 12:00 МСК происходит обновление котировок).
 */
$kzt = get_currency('KZT', 2); // запрос к ЦБ для получения xml.

$xml = simplexml_load_file('/home/bitrix/www/local/webhooks/currency.xml');
$currency = (json_decode(json_encode($xml), true));

// Перебираем полученный массив из ЦБ и получаем нужные нам валюты. Валюты из выгрузки идут в алфавитном порядке.
foreach ($currency['Valute'] as $value) {
    if ($value['CharCode'] == 'EUR') {
        $nominal = $value['Nominal'];
        $amount = (float) number_format((float) str_replace(",", ".", $value['Value']), 2, ".", "");
        $name = $value['CharCode'];

        // Создается элемент УС для хранения данных о котировке валюты на опредленный день.
        $random = random_bytes(10);
        $random = (bin2hex($random));
        $elementAdd = executeREST(
            'lists.element.add',
            array(
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => '95',
                'ELEMENT_CODE' => $random,
                'FIELDS' => array(
                    'NAME' => 'EUR',
                    'PROPERTY_476' => $amount,
                    'PROPERTY_479' => $dateForB24,
                ),
            ),
            $domain, $auth, $user);

        // Обновляем справочник валюты в Б24 для корректного расчета сумм по отчетам и сделкам в режиме канбан.
        $updateCurrency = executeREST(
            'crm.currency.update',
            array(
                'ID' => 'EUR',
                'FIELDS' => array(
                    'AMOUNT_CNT' => 1,
                    'AMOUNT' => $amount,
                ),
            ),
            $domain, $auth, $user);

    } elseif ($value['CharCode'] == 'KZT') {
        // Аналогичные прцедуры проделываем для Тенге.
        $nominal = $value['Nominal'];
        $amount = (float) number_format((float) str_replace(",", ".", $value['Value']), 2, ".", "");
        $name = $value['CharCode'];
        $amount = $amount / $nominal;

        $updateCurrency = executeREST(
            'crm.currency.update',
            array(
                'ID' => 'KZT',
                'FIELDS' => array(
                    'AMOUNT_CNT' => 1,
                    'AMOUNT' => $amount,
                ),
            ),
            $domain, $auth, $user);

        $random = random_bytes(10);
        $random = (bin2hex($random));
        $elementAdd = executeREST(
            'lists.element.add',
            array(
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => '95',
                'ELEMENT_CODE' => $random,
                'FIELDS' => array(
                    'NAME' => 'KZT',
                    'PROPERTY_476' => $amount,
                    'PROPERTY_479' => $dateForB24,
                ),
            ),
            $domain, $auth, $user);

    } elseif ($value['CharCode'] == 'USD') {
        // Аналогичные прцедуры проделываем для Доллара.
        $nominal = $value['Nominal'];
        $amount = (float) number_format((float) str_replace(",", ".", $value['Value']), 2, ".", "");
        $name = $value['CharCode'];

        $updateCurrency = executeREST(
            'crm.currency.update',
            array(
                'ID' => 'USD',
                'FIELDS' => array(
                    'AMOUNT_CNT' => 1,
                    'AMOUNT' => $amount,
                ),
            ),
            $domain, $auth, $user);

        $random = random_bytes(10);
        $random = (bin2hex($random));
        $elementAdd = executeREST(
            'lists.element.add',
            array(
                'IBLOCK_TYPE_ID' => 'lists',
                'IBLOCK_ID' => '95',
                'ELEMENT_CODE' => $random,
                'FIELDS' => array(
                    'NAME' => 'USD',
                    'PROPERTY_476' => $amount,
                    'PROPERTY_479' => $dateForB24,
                ),
            ),
            $domain, $auth, $user);

    }
}

function executeREST($method, array $params, $domain, $auth, $user)
{
    $queryUrl = 'https://' . $domain . '/rest/' . $user . '/' . $auth . '/' . $method . '.json';
    $queryData = http_build_query($params);
    $curl = curl_init();
    curl_setopt_array($curl, array(
        CURLOPT_SSL_VERIFYPEER => 0,
        CURLOPT_POST => 1,
        CURLOPT_HEADER => 0,
        CURLOPT_RETURNTRANSFER => 1,
        CURLOPT_URL => $queryUrl,
        CURLOPT_POSTFIELDS => $queryData,
    ));
    return json_decode(curl_exec($curl), true);
    curl_close($curl);
}

function writeToLog($data, $title = '')
{
    $log = "\n------------------------\n";
    $log .= date("Y.m.d G:i:s") . "\n";
    $log .= (strlen($title) > 0 ? $title : 'DEBUG') . "\n";
    $log .= print_r($data, 1);
    $log .= "\n------------------------\n";
    file_put_contents(getcwd() . '/logs/getCurrency.log', $log, FILE_APPEND);
    return true;
}

function get_currency($currency_code, $format)
{

    $date = date('d/m/Y'); // Текущая дата

    $cache_time_out = 3600; // Время жизни кэша в секундах (3600)

    $file_currency_cache = './currency.xml'; // Файл кэша

    if (!is_file($file_currency_cache) || filemtime($file_currency_cache) < (time() - $cache_time_out)) {

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://www.cbr.ru/scripts/XML_daily.asp?date_req=' . $date);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);

        $out = curl_exec($ch);

        curl_close($ch);

        file_put_contents($file_currency_cache, $out);

    }

    $content_currency = simplexml_load_file($file_currency_cache);

    return number_format(str_replace(',', '.', $content_currency->xpath('Valute[CharCode="' . $currency_code . '"]')[0]->Value, $content_currency->xpath('Valute[CharCode="' . $currency_code . '"]')[0]->Nominal, ), $format);

}
?>