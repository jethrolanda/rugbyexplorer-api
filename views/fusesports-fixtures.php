<h2>Schedule and Scores<br><small><?php echo $data['full_name']; ?></small></h2>

<?php
foreach ($fixtures['round_objects'] as $round) {
  $custom_format = 'j F Y';
  $games = $round['games'];
  usort($games, fn($a, $b) => strtotime($b['gamedate']) <=> strtotime($a['gamedate']));
  $recentDateTime = new DateTime($games[0]['gamedate'], new DateTimeZone('UTC')); // most recent
  echo '<h3 class="round">' . $round['name'] . ' - ' . esc_html($recentDateTime->format($custom_format)) . '</h3>';
?>
  <table class="fixtures" border cellspacing="0">
    <thead>
      <tr>
        <th>Team</th>
        <th class="points">Score</th>
        <th class="vs"></th>
        <th class="points">Score</th>
        <th>Team</th>
        <th>Location</th>
        <th>Date/Time</th>
      </tr>
    </thead>
    <tbody class="notranslate">
      <?php
      $game_date_format = 'j/n/Y h:i A';
      foreach ($round['games'] as $game) {
        $datetime = new DateTime($game['gamedate'], new DateTimeZone('UTC')); ?>
        <tr class="withIcon">
          <td class="team"><?php echo $game['hmteam']['team_name']; ?></td>
          <td class="points"><?php echo $game['hmscore'] ?: 0; ?></td>
          <td class="vs">v</td>
          <td class="points"><?php echo $game['awteam']['team_name']; ?></td>
          <td class="team"><?php echo $game['awscore'] ?: 0; ?></td>
          <td data-label="Location" class="location"><?php echo $game['location']; ?></td>
          <td data-label="Date/Time"><?php echo esc_html($datetime->format($game_date_format)); ?></td>
        </tr>
      <?php
      }
      ?>

    </tbody>
  </table>
<?php
}
?>