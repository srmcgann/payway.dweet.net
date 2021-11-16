<?
  require('db.php');
  $data = json_decode(file_get_contents('php://input'));
  $userName = mysqli_real_escape_string($link, $data->{'userName'});
  $passhash = mysqli_real_escape_string($link, $data->{'passhash'});
  $currency = mysqli_real_escape_string($link, $data->{'currency'});
  $featured = mysqli_real_escape_string($link, $data->{'featured'});
  $success = false;
  $sql = 'SELECT * FROM users WHERE isAdmin = 1 AND name LIKE "'.$userName.'" AND passhash = "'.$passhash.'"';
  $res = mysqli_query($link, $sql);
  if(mysqli_num_rows($res)){
    $row = mysqli_fetch_assoc($res);
    if($row['enabled']){
      $sql = 'UPDATE currencies SET featured = ' . $featured . ' WHERE currency = "' . $currency . '"';
      mysqli_query($link, $sql);
      $success = true;
    }
  }
  echo json_encode([$success]);
?>
