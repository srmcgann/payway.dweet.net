<?
  require('db.php');
  $data = json_decode(file_get_contents('php://input'));
  $userName = mysqli_real_escape_string($link, $data->{'userName'});
  $passhash = mysqli_real_escape_string($link, $data->{'passhash'});
  $sql='SELECT balance FROM users WHERE name LIKE "' . $userName . '" AND passhash = "' . $passhash . '"';
  if($res = mysqli_query($link, $sql)){
    $balance = mysqli_fetch_assoc($res)['balance'];
    echo json_encode([true, $balance]);
  } else {
    echo json_encode([false]);
  }
?>

