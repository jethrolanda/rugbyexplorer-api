<h2><?php echo $season; ?></h2>
<table>
  <thead>
    <tr>
      <th>League</th>
      <th>Matches</th>
      <th>Points</th>
      <th>Tries</th>
      <th>Conversions</th>
      <th>Penalty Kicks</th>
      <th>Drop Goals</th>
    </tr>
  </thead>
  <?php
  $total = array(
    'matches' => 0,
    'points' => 0,
    'try' => 0,
    'conversions' => 0,
    'penalty_kicks' => 0,
    'drop_goals' => 0,
  );
  foreach ($league_stats as $stat) {
    $total['matches'] += $stat['total_matches'];
    $total['points'] += $stat['total_points'];
    $total['try'] += $stat['total_try'];
    $total['conversions'] += $stat['total_conversions'];
    $total['penalty_kicks'] += $stat['total_penalty_kicks'];
    $total['drop_goals'] += $stat['total_drop_goals'];
  ?>
    <tr>
      <td><?php echo esc_html($stat['league_name']); ?></td>
      <td><?php echo esc_html($stat['total_matches']); ?></td>
      <td><?php echo esc_html($stat['total_points']); ?></td>
      <td><?php echo esc_html($stat['total_try']); ?></td>
      <td><?php echo esc_html($stat['total_conversions']); ?></td>
      <td><?php echo esc_html($stat['total_penalty_kicks']); ?></td>
      <td><?php echo esc_html($stat['total_drop_goals']); ?></td>
    </tr>
  <?php
  }
  ?>
  <tfoot>
    <tr>
      <th>Total</th>
      <th><?php echo $total['matches']; ?></th>
      <th><?php echo $total['points']; ?></th>
      <th><?php echo $total['try']; ?></th>
      <th><?php echo $total['conversions']; ?></th>
      <th><?php echo $total['penalty_kicks']; ?></th>
      <th><?php echo $total['drop_goals']; ?></th>
    </tr>
  </tfoot>
</table>