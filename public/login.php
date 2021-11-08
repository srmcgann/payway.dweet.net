<?
  require('db.php');
  $data = json_decode(file_get_contents('php://input'));
  $userName = mysqli_real_escape_string($link, $data->{'userName'});
  $password = mysqli_real_escape_string($link, $data->{'password'});
  $sql='SELECT * FROM users WHERE name LIKE "' . $userName . '"';
  if($res = mysqli_query($link, $sql)){
    $row = mysqli_fetch_assoc($res);
    $passhash = $row['passhash'];
    if(password_verify($password, $passhash)){
      echo json_encode([!!mysqli_num_rows($res), $row['name'], $row['id'], $row['passhash'], $row['avatar'], $row['transactionsPerPage'], !!$row['isAdmin']]);
    }else{
      echo json_encode([false]);
    }
  } else {
    echo json_encode([false]);
  }
?>

