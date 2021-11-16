<?
  require('db.php');
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, 'https://rest.coinapi.io/v1/assets');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET' );
  curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    "X-CoinAPI-Key: 7C024898-0DA8-4B9C-8044-8C9EDB9AC22B",
  ));
  $json = json_decode(curl_exec($ch));
  if(sizeof($json)){
    $assets = [];
    $featuredCurrencies = [
      'BTC',
      'ETH',
      'XRP',
      'HNS',
      'MATIC'
    ];
    $history = [];
    foreach($json as $el => $val){
      if(isset($val->{'price_usd'}) && in_array($val->{'asset_id'}, $featuredCurrencies)){
        $assets[] = $val->{'asset_id'};
        $sql = 'SELECT id FROM currencies WHERE currency = "' . $val->{'asset_id'} . '"';
        $res = mysqli_query($link, $sql);
        if(!mysqli_num_rows($res)){
          $sql = 'INSERT INTO currencies (name, last_sync, currency) VALUES("' . $val->{'name'} . '", now(), "' . $val->{'asset_id'} . '")';
          mysqli_query($link, $sql);
        }
        $sql = 'UPDATE currencies SET name = "' . $val->{'name'} . '", last_sync = now(), price_usd = "' . $val->{'price_usd'} . '" WHERE currency = "' . $val->{'asset_id'} . '"';
        mysqli_query($link, $sql);
        $history[] = ($l = [
          'currency' => $val->{'asset_id'},
          'name' => $val->{'name'},
          'price_usd' => $val->{'price_usd'}
        ]);
        echo json_encode($l) . "\n";
      }
    }
    $sql = 'INSERT INTO history (currencyData) VALUES ("' . mysqli_real_escape_string($link, json_encode($history)) . '")';
    mysqli_query($link, $sql);
  }
  $sql = 'DELETE FROM history WHERE date < (NOW() - INTERVAL 1 DAY)';
  mysqli_query($link, $sql);
  echo 'finished inserting / updating ' . sizeof($assets) . " asset names.\n";
?>
