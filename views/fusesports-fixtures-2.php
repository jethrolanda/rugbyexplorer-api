<h2>Schedule and Scores<br><small><?php echo $data['full_name']; ?></small></h2>

<?php
foreach ($data['rounds'] as $name => $games) {
  $custom_format = 'j F Y';
  echo '<h3 class="round">' . $name . ' - ' . wp_date($custom_format, strtotime($games[0]['GameDate'])) . '</h3>';
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
      foreach ($games as $game) { ?>
        <tr class="withIcon">
          <td class="team"><?php echo $game['home_team_name']; ?></td>
          <td class="points"><?php echo $game['home_team_score']; ?></td>
          <td class="vs">v</td>
          <td class="points"><?php echo $game['away_team_score']; ?></td>
          <td class="team"><?php echo $game['away_team_name']; ?></td>
          <td data-label="Location" class="location"><?php echo $game['location']; ?></td>
          <td data-label="Date/Time"><?php echo wp_date($game_date_format, strtotime($games[0]['GameDate'])); ?></td>
        </tr>
      <?php
      }
      ?>

    </tbody>
  </table>
<?php
}
?>