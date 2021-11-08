<?
  require('db.php');
  $data = json_decode(file_get_contents('php://input'));
  $userName = mysqli_real_escape_string($link, $data->{'userName'});
  $passhash = mysqli_real_escape_string($link, $data->{'passhash'});
  $historyPage = mysqli_real_escape_string($link, $data->{'historyPage'});
  $sql='SELECT balance, id, transactionsPerPage, avatar, isAdmin FROM users WHERE name LIKE "' . $userName . '" AND passhash = "' . $passhash . '"';
  if($res = mysqli_query($link, $sql)){
    $row = mysqli_fetch_assoc($res);
    $avatar = $row['avatar'];
    $isAdmin = $row['isAdmin'];
    $itemsPerPage = $row['transactionsPerPage'];
    $balance = $row['balance'];
    $userID = $row['id'];
    $start = $historyPage * $itemsPerPage;
    $sql = 'SELECT * FROM transactions WHERE userID = ' . $userID . ' ORDER BY DATE DESC LIMIT ' . $start . ', ' . $itemsPerPage;
    $res = mysqli_query($link, $sql);
    for($i = 0; $i < mysqli_num_rows($res); ++$i){
      $row = mysqli_fetch_assoc($res);
      $d = new DateTime($row['date']);
      $row['date'] = $d->format('m/d/Y');
      $row['time'] = $d->format('h:iA (T)');
      if(substr($row['time'],0,1) == '0') $row['time'] = substr($row['time'],1);
      if($row['relatedTransactionID']){
        $sql = 'SELECT * FROM transactions where id = ' . $row['relatedTransactionID'];
        $res2 = mysqli_query($link, $sql);
        $row2 = mysqli_fetch_assoc($res2); 
        $sql = 'SELECT name, avatar FROM users WHERE id = ' . $row2['userID'];
        $res3 = mysqli_query($link, $sql);
        $row3 = mysqli_fetch_assoc($res3);
        $row2['userName'] = $row3['name'];
        $row2['userAvatar'] = $row3['avatar'];
        $row['related'] = $row2;
      }
      $history[] = $row;
    }
    $sql = 'SELECT * FROM transactions WHERE userID = ' . $userID;
    $res = mysqli_query($link, $sql);
    $pages = floor(mysqli_num_rows($res) / ($itemsPerPage + .1)) + 1;
    $globalTotal = null;
    if($isAdmin){
      $sql = 'SELECT * FROM users';
      $res_ = mysqli_query($link, $sql);
      $total = 0;
      for($i=0; $i<mysqli_num_rows($res_); ++$i){
        $row_ = mysqli_fetch_assoc($res_);
        $total += $row_['balance'];
      }
      $globalTotal = $total;
    }
    echo json_encode([true, $balance, $history, $pages, $globalTotal]);
  } else {
    echo json_encode([false]);
  }
?>

