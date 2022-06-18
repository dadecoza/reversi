<?php
class Reversi {

    private $conn;
    private $board;
    private $session;
    private $state;
    
    public $white = 1;
    public $black = 2;
    
    function __construct($db_server, $db_username, $db_password, $db_database) {
        $this->conn = new mysqli(
            $db_server,
            $db_username, 
            $db_password,
            $db_database
        );
        if ($this->conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }
        $this->cleanup();
        $this->clear_board();
    }

    function __destruct() {
        $this->store_game();
        $this->conn->close();
    }

    function board_rows() {
        return sizeof($this->board);
    }

    function board_cols() {
        return sizeof($this->board[0]);
    }

    function board_size() {
        return $this->board_rows()*$this->board_cols();
    }

    function coords_from_int($i) {
        $row = intval($i / $this->board_rows());
        $col = intval($i % $this->board_cols());
        return array($row, $col);
    }

    function put_piece($coords, $piece) {
        $row = $coords[0];
        $col = $coords[1];
        $this->board[$row][$col] = $piece;
    }

    function get_piece($coords) {
        $y = $coords[0];
        $x = $coords[1];
        if (($y < 0)||($y > $this->board_rows()-1)) {
            return -1;
        }
        if (($x < 0)||($x > $this->board_cols()-1)) {
            return -1;
        }
        return $this->board[$y][$x];
    }

    function piece_count($piece) {
        $pc = 0;
        for ($i=0; $i<$this->board_size(); $i++) {
            $coords = $this->coords_from_int($i);
            $piece_at_coords = $this->get_piece($coords);
            if ($piece_at_coords == $piece) {
                $pc++;
            }
        }
        return $pc;
    }

    function your_move($pos) {
        if ($pos >= $this->board_size()) {return;}
        $coords = $this->coords_from_int($pos);
        $piece = $this->black;
        if ($this->is_valid_move($coords, $piece)) {
            $this->flip($coords, $piece, true);
            $this->put_piece($coords, $piece);
            if ($this->game_over()) {
                $this->state = "GAMEOVER";
            } elseif ($this->can_move($this->white)) {
                $this->state = "MYTURN";
            } else {
                $this->state = "YOURTURN";
            }
        }
    }

    function my_move() {
        $piece = 1;
        $coords = $this->get_best_move($piece);
        if (isset($coords)) {
            $this->flip($coords, $piece, true);
            $this->put_piece($coords, $piece);
        }
        if ($this->game_over()) {
            $this->state = "GAMEOVER";
        } elseif ($this->can_move($this->black)) {
            $this->state = "YOURTURN";
        } else {
            $this->state = "MYTURN";
        }
    }

    function play($i) {
        $state = $this->state;
        if ($state == "YOURTURN") {
            $this->your_move($i);     
        } elseif ($this->state == "MYTURN") {
            $this->my_move();
        }
    }

    function get_int_from_coords($coords) {
        $row = $coords[0];
        $col = $coords[1];
        return ($row * $this->board_rows()) + $col;
    }

    function put_from_int($i, $piece) {
        $coords = $this->coords_from_int($i);
        $this->put_piece($coords, $piece);
    }

    function get_state() {
        return $this->state;
    }

    function get_session() {
        return $this->session;
    }

    function create_session_str() {
        $chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
        $arr = array();
        for($i=0; $i<5; $i++) {
            $len = strlen($chars);
            $c = substr($chars, rand(0, $len), 1);
            array_push($arr, $c);
        }
        return implode("", $arr);
    }

    function clear_board() {
        $this->board = array(
            array(0, 0, 0, 0, 0, 0, 0, 0),
            array(0, 0, 0, 0, 0, 0, 0, 0),
            array(0, 0, 0, 0, 0, 0, 0, 0),
            array(0, 0, 0, 0, 0, 0, 0, 0),
            array(0, 0, 0, 0, 0, 0, 0, 0),
            array(0, 0, 0, 0, 0, 0, 0, 0),
            array(0, 0, 0, 0, 0, 0, 0, 0),
            array(0, 0, 0, 0, 0, 0, 0, 0)
        ); 
        return $this->board;
    }

    function default_board() {
        $y = $this->board_rows()/2;
        $x = $this->board_cols()/2;
        $this->board[$y-1][$x-1] = 1;
        $this->board[$y-1][$x] = 2;
        $this->board[$y][$x-1] = 2;
        $this->board[$y][$x] = 1;
    }

    function init_board($strboard) {
        $this->clear_board();
        $strboard_length = strlen($strboard);
        
        if ($strboard_length != $this->board_size()) {
            return $this->board();
        }

        for ($i=0; $i < $strboard_length; $i++) {
            $c = intval(substr($strboard, $i, 1));
            $this->put_from_int($i, $c);
        }
        return $this->board;
    }

    function board_to_string() {
        $str = "";
        for ($y=0; $y<$this->board_rows(); $y++) {
            for ($x=0;$x<$this->board_cols(); $x++) {
                $str .= $this->board[$y][$x];
            }
        }
        return $str;
    }

    function render_piece($coords) {
        $p = $this->get_piece($coords);
        if ($p == $this->white) {
            return "<font color='#FFFFFF' size='5'>&nbsp;&#8226;&nbsp;</font>";
        } elseif ($p == $this->black) {
            return "<font color='#000000' size='5'>&nbsp;&#8226;&nbsp;</font>";
        }
        $pos = $this->get_int_from_coords($coords);
        $ses = $this->session;
        $state = $this->state;
        $valid = $this->is_valid_move($coords, $this->black);
        if (($state == "YOURTURN")&&($valid)) {
            return "<a href='?pos=$pos&ses=$ses'><font size='5' color='#66CC00'>&nbsp;&nbsp;&nbsp;</font></a>";
        }
        return "<font size='5' color='#66CC00'>&nbsp;&nbsp;&nbsp;</font>";
    }

    function flip($coords, $piece, $doflip) {
        if ($this->get_piece($coords) != 0) return 0;
        $opponent = ($piece == 1) ? 2 : 1;
        $flips = array();
        for ($i=0; $i<8; $i++) {
            $y = $coords[0];
            $x = $coords[1];
            $flip_list = array();
            while (true) {
                if ($i == 0)  {$y--;}
                elseif ($i == 1) {$y++;}
                elseif ($i == 2) {$x--;}
                elseif ($i == 3) {$x++;}
                elseif ($i == 4) {$y--; $x--;}
                elseif ($i == 5) {$y++; $x--;}
                elseif ($i == 6) {$y--; $x++;}
                elseif ($i == 7) {$y++; $x++;}
                $flip_coords = array($y, $x);
                $p = $this->get_piece($flip_coords);
                if ($p < 1) {
                    break;
                }
                elseif ($p == $opponent) {
                    array_push($flip_list, $flip_coords);
                }
                elseif ($p == $piece) {
                    $flips = array_merge($flips, $flip_list);
                    break;
                }
            }
        }
        
        if ($doflip) {
            for ($i=0; $i<sizeof($flips); $i++) {
                $this->put_piece($flips[$i], $piece);
            }
        }

        return sizeof($flips);
    }

    function is_valid_move($coords, $piece) {
        return ($this->flip($coords, $piece, false) > 0);
    }

    function can_move($piece) {
        for ($i=0; $i<$this->board_size(); $i++) {
            $coords = $this->coords_from_int($i);
            if ($this->is_valid_move($coords, $piece)) {
                return true;
            }
        }
        return false;
    }

    function game_over() {
        return ((!$this->can_move($this->black)) && (!$this->can_move($this->white)));
    }

    function get_best_move($piece) {
        $best = 0;
        $best_coords = array();
        for ($i=0; $i<$this->board_size(); $i++) {
            $coords = $this->coords_from_int($i);
            $count = $this->flip($coords, $piece, false);
            if ($count > $best) {
                $best = $count;
                $best_coords = $coords;
            }
        }
        if ($best > 0) {
            return $best_coords;
        }
        return null;
    }

    function html() {
        $board_html = "<table border='1' bgcolor='#66CC00'>\n";
        for ($y=0; $y<$this->board_rows(); $y++) {
            $board_html .= "\t<tr>\n";
            for ($x=0; $x<$this->board_cols(); $x++) {
                $coords = array($y, $x);
                $p = $this->render_piece($coords);
                $board_html .= "\t\t<td>$p</td>\n";
            }
            $board_html .= "\t</tr>\n";
        }
        $board_html .= "</table>\n";
        $board_html .= "<table cellspacing='2' cellpadding='5'>\n";
        $board_html .= "\t<tr>\n";
        $board_html .= "\t\t<td bgcolor='#FFFFFF'><font color='#000000'>".$this->piece_count($this->white)."</font></td>\n";
        $board_html .= "\t\t<td bgcolor='#000000'><font color='#FFFFFF'>".$this->piece_count($this->black)."</font></td>\n";
        $board_html .= "\t</tr>\n";
        $board_html .= "</table>\n";
        return $board_html;
    }

    function new_game() {
        $ts = time();
        $state = "YOURTURN";
        $session = $this->create_session_str();
        $board = "0000000000000000000000000000000000000000000000000000000000000000";
        $sql = "INSERT INTO reversi (id, board, ts, state) values (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssis", $session, $board, $ts, $state);
        $stmt->execute();
        $stmt->close();
        $this->session = $session;
        $this->state = $state;
        $this->default_board();
        return $this->session;
    }

    function resume_game($session) {
        $sql = "SELECT id, board, ts, state FROM reversi WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("s", $session);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $this->init_board($row["board"]);
                $this->session = $row["id"];
                $this->state = $row["state"];
                break;
            }
        } else {
            $this->new_game();
        }
        $stmt->close();
        return $this->session;
    }

    function store_game() {
        if (!$this->session) return;
        $ts = time();
        $board = $this->board_to_string();
        $session = $this->session;
        $state = $this->state;
        $sql = "UPDATE reversi SET board = ?, state = ?, ts = ? WHERE id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ssis", 
            $board,
            $state,
            $ts,
            $session
        );
        $stmt->execute();
        $stmt->close();
        return;
    }

    function cleanup() {
        $ts = time()-86400;
        $sql = "DELETE FROM reversi WHERE ts < ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("i", $ts);
        $stmt->execute();
        $stmt->close();
        return;
    }
}
