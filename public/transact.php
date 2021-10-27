<?php
  require('db.php');
  $data = json_decode(file_get_contents('php://input'));
  $userName = mysqli_real_escape_string($link, $data->{'userName'});
  $passhash = mysqli_real_escape_string($link, $data->{'passhash'});
  $amount = mysqli_real_escape_string($link, $data->{'amount'});
  $transactionType = mysqli_real_escape_string($link, $data->{'transactionType'});
  $transactionPartner = mysqli_real_escape_string($link, $data->{'transactionPartner'});
  $sql = 'SELECT * FROM users WHERE name LIKE "' . $userName . '" AND passhash = "'.$passhash.'";';
  $res = mysqli_query($link, $sql);
  if(mysqli_num_rows($res)){
    $row = mysqli_fetch_assoc($res);
    $userID = $row['id'];
    $fmt = numfmt_create( 'en_US', NumberFormatter::CURRENCY );
    $pennies = (numfmt_parse_currency($fmt, $amount, $curr)) * 100;
    $sql1 = 'INSERT INTO transactions (userID, type, amount) VALUES(' . $userID . ', "' . $transactionType . '", ' . $pennies . ')';
    mysqli_query($link, $sql1);
    $balance = $row['balance'] + $pennies * ($transactionType == 'withdrawal' || $transactionType == 'send' ? -1 : 1);
    $sql = 'UPDATE users SET balance = ' . $balance . ' WHERE id = ' . $userID;
    mysqli_query($link, $sql);
    if($transactionType == 'send' || $transactionType =='request'){
      $sql = 'SELECT id, balance FROM users WHERE name LIKE "' . $transactionPartner . '"';
      $res = mysqli_query($link, $sql);
      if(mysqli_num_rows($res)){
        $row = mysqli_fetch_assoc($res);
        $partnerBalance = $row['balance'] + $pennies * ($transactionType == 'send' ? 1 : -1);
        $partnerID = $row['id'];
        $sql = 'UPDATE users SET balance = ' . $partnerBalance . ' WHERE id = ' . $partnerID;
        mysqli_query($link, $sql);
      }
    }
    echo json_encode([true, $balance]);
  } else {
    echo json_encode([false,'']);
  }
?>
