<?
  require('db.php');
  $sql='SELECT * FROM users';
  $res = mysqli_query($link, $sql);
  $total = 0;
  for($i=0; $i<mysqli_num_rows($res); ++$i){
    $row = mysqli_fetch_assoc($res);
    $total += $row['balance'];
  }
  echo 'TOTAL CURRENT FUNDS FOR ALL PAYWAY ACCOUNTS' . "\n" . money_format('$%=*(#10.2n',+$total/100) . "\n";
?>
