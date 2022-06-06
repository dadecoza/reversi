<?php
require('reversi.php');
$configs = include('config.php');
$pos = intval(isset($_GET["pos"]) ? filter_var($_GET["pos"], FILTER_SANITIZE_STRING) : "100");
$ses = isset($_GET["ses"]) ? filter_var($_GET["ses"], FILTER_SANITIZE_STRING) : "";
$game = new Reversi(
    $configs['servername'],
    $configs['username'],
    $configs['password'],
    $configs['database']
);

if ($ses) {
    $game->resume_game($ses);
} else {
    $game->new_game();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<title>Reversi</title>
</head>
<body bgcolor="#E5E7E9">
<?php
$game->play($pos);
echo $game->html();
if ($game->get_state() == "GAMEOVER") {
    echo "<p>Game Over!</p>\n";
    if ($game->piece_count($game->white) > $game->piece_count($game->black)) {
        echo "<p>I win.</p>\n";
    } elseif ($game->piece_count($game->white) < $game->piece_count($game->black)) {
        echo "<p>You win.</p>\n";
    } else {
        echo "<p>It is a tie.</p>\n";
    }
    echo "<form><p><input type='hidden' name='ses' value=''><input type='submit' value='Continue'></p></form>";
} elseif ($game->get_state() == "YOURTURN") {
    echo "<p>Make your move ...</p>\n";
} elseif ($game->get_state() == "MYTURN") {
    $ses = $game->get_session();
    echo "<p>My turn ...</p>\n";
    echo "<form><p><input type='hidden' name='ses' value='$ses'><input type='submit' value='Continue'></p></form>";
}
?>
</body>
</html>


