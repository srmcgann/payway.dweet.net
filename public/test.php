<?
    $fmt = numfmt_create( 'en_US', NumberFormatter::CURRENCY );
    $num = "$123,456.99";
    echo numfmt_parse_currency($fmt, $num, $curr) . "\n";
?>
