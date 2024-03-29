<?php

  require 'database.php';
  require 'functions.php';

  session_start();
  check_logged_in();
  is_admin(); 

  $username = $_SESSION['username'];
  $profile_picture = $_SESSION['profile_picture'];

  if (isset($_FILES["upload"]["name"])) {
    $target_dir = "/media/main/www/html/fileServ/files/".$username."/";
    $target_file = $target_dir . basename($_FILES["upload"]["name"]);
    $web_target_file = "./files/".$username."/".basename($_FILES["upload"]["name"]);
    $uploadOk = 1;
    $extension = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
    $filesize = $_FILES['upload']['size'];
    $filename = $_FILES['upload']['name'];
  }

  if (!isset($_POST["submit"])) {
    $uploadOk = 0;
    $error = "";
  } else if (file_exists($target_file)) {
    $uploadOk = 0;
    $error = "File Already Exists";
  }

  if ($uploadOk == 1) {
    if (!file_exists($target_dir)) {
      mkdir($target_dir,0770, true);

    }
    if (!move_uploaded_file($_FILES["upload"]["tmp_name"], $target_file)) {
      $uploadOk = 0;
    }
    if ($uploadOk == 1) {

      $pdo = Database::connect();
      $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
      $sql = "INSERT INTO Files (ID, username,filename,filesize,filetype,filePath) values(?, ?, ?, ?, ?, ?)"; 
      $q = $pdo->prepare($sql);
      $q->execute(array(null,$username,$filename,$filesize,$extension,$web_target_file));
      Database::disconnect();
    }
  }

  $pictures = array('jpg','png','gif','svg','tiff','bmp');
  $musics = array('wav','mp3','ogg');

?>

<html>
  <head>
    <title>Main Page</title>
    <link rel='stylesheet' type='text/css' href='./css/mss.css' />
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
    <script src='https://ajax.googleapis.com/ajax/libs/jquery/3.2.0/jquery.min.js'></script>
    <script src='https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js'></script>
  <head>

  <body>

    <?php print_navbar($profile_picture, $username); ?>

    <div class="container-fluid">
      <div class="row">
        <div class="col-lg">
          <form action="main.php" method="post" enctype="multipart/form-data">
            <div class="form-group">
              <label for="upload">Select a File to Upload:</label>
              <input type="file" class="form-control-file" name="upload" id="upload">
            </div>
            <input type="submit" class="btn btn-primary" value="Upload File" name="submit">
          </form>
        </div>
      </div>
    </div>

    <table class='table'>
      <thead>
	      <tr>
          <?php echo (ADMIN === True) ? '<th>Owner</th>' : ''; ?>
		      <th>Filename</th>
		      <th>File Size (Bytes)</th>
		      <th>File Type</th>
	      </tr>
	    </thead>
      <?php
        $pdo = Database::connect();
        if (ADMIN === TRUE) {
          $sql = 'SELECT * FROM `Files` ORDER BY `filename`';
          $data = $pdo->query($sql)->fetchAll();
        } else {
          $sql = 'SELECT * FROM `Files` WHERE `username`= ? ORDER BY `filename`';
          $q = $pdo->prepare($sql);
          $q->execute([$username]);
          $data = $q->fetchAll();
        }
        foreach($data as $row) {
          echo '<tr>';
            echo (ADMIN === True) ? '<td>'. $row['username'] . '</td>' : '';
            echo '<td>'. $row['filename'] . '</td>';
            echo '<td>'. $row['filesize'] . '</td>';
            echo '<td>'. $row['filetype'] . '</td>';
            echo '<td width=300>';
              echo (in_array(strtolower($row['filetype']), $pictures)) ? '<a class="btn btn-primary" href="'.$row['filePath'].'" target="_blank" >View</a>' : '';
              echo (in_array(strtolower($row['filetype']), $musics)) ? '<a class="btn btn-primary" href="'.$row['filePath'].'" target="_blank" >Listen</a>' : '';
              echo (in_array(strtolower($row['filetype']), ['txt','pdf'])) ? '<a class="btn btn-primary" href="'.$row['filePath'].'" target=_blank">Read</a>' : '';
              echo (in_array(strtolower($row['filetype']), ['xml','xlsx', 'ods'])) ? '<a class="btn btn-primary" href="spreadsheet_reader.php?id=' . $row['ID'] .'" target=_blank">View</a>' : '';
              echo (in_array(strtolower($row['filetype']), ['zip'])) ? '<a class="btn btn-primary" href="archive_viewer.php?id=' . $row['ID'] .'" target=_blank">See Contents</a>' : '';
              echo '&nbsp;';
              echo '<a class="btn btn-success" href="'.$row['filePath'].'" download>Download</a>';
              echo '&nbsp;';
              echo '<a class="btn btn-danger" href="delete.php?id='.$row['ID'].'&path=\''.$row['filePath'].'\'">Delete</a>';
            echo '</td>';
          echo '</tr>';
        }
        Database::disconnect();
      ?>
    </table>
  </body>
</html>
