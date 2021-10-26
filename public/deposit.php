<?
  require('db.php');
  $data = json_decode(file_get_contents('php://input'));
  $userName = mysqli_real_escape_string($link, $data->{'userName'});
  $passhash = mysqli_real_escape_string($link, $data->{'passhash'});
  $amount = mysqli_real_escape_string($link, $data->{'amount'});
  $sql = 'SELECT * FROM users WHERE name LIKE "' . $userName . '" AND passhash LIKE "'.$passhash.'";';
  $res = mysqli_query($link, $sql);
  if(mysqli_num_rows($res)){
    $row = mysqli_fetch_assoc($res);
    $userID = $row['id'];
    $sql = 'INSERT INTO transactions (userID, type, amount) VALUES('.$userID.', "deposit", '.$amount.')';
    mysqli_query($link, $sql);
    $bal = $row['balance'] + $amount;
    $sql = 'UPDATE users SET balance = '.$balance.' WHERE id = ' . $userID;
    $mysql_query($link, $sql);
    echo json_encode([true]);
  } else {
    echo json_encode([false,'']);
  }
?>
