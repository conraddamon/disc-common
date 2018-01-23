<?php

# returns a title comprising tournament year and name
function getTournamentTitle($tournamentId) {

  $data = is_array($tournamentId) ? $tournamentId : null;
  if (!$data) {
    $sql = "SELECT * FROM tournament WHERE id=$tournamentId";
    $data = db_query($sql, 'one');
  }
  list($year, $mm, $dd) = explode('-', $data['start']);
  return $year . ' ' . $data['name'];
}

# returns a header with centered text showing tournament info
function getTournamentHeader($tournamentId) {

  $data = is_array($tournamentId) ? $tournamentId : null;
  if (!$data) {
    $sql = "SELECT * FROM tournament WHERE id=$tournamentId";
    $data = db_query($sql, 'one');
  }
  $title = getTournamentTitle($data);
  $location = $data['location'];
  $start = date("F j", strtotime($data['start']));
  $end = date("F j", strtotime($data['end']));
  $location .= $start != $end ? ", $start - $end" : ", $start";
  $url = $data['url'];
  if ($url) {
    $title = "<a href='$url'>$title</a>";
  }
  $note = $data['note'];

  return "<div class='title'>$title</div><div class='location'>$location</div><div class='tournamentNote'>$note</div>";
}

?>
