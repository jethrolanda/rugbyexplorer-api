<?php
foreach ($data['ladderPools'] as $pool) {

  if ($pool['poolName'] != 'pool-default') {
    echo "<h2>" . $pool['poolName'] . "</h2>";
  }
?>
  <table class="css-ixkv1t">
    <thead>
      <tr>
        <th title="Position" class="css-3l0rbe">Pos</th>
        <th class="css-3l0rbe">Team</th>
        <th class="css-1th2hky"><span class="css-wvps4y">Played</span></th>
        <th class="css-sxtr9y"><span class="css-wvps4y">Won</span></th>
        <th class="css-sxtr9y"><span class="css-wvps4y">Draw</span></th>
        <th class="css-sxtr9y"><span class="css-wvps4y">Loss</span></th>
        <th title="Points Difference" class="css-1th2hky">+/-</th>
        <th class="css-sxtr9y"><span class="css-wvps4y">Bonus Points</span></th>
        <th class="css-1th2hky"><span class="css-wvps4y">Points</span></th>
      </tr>
    </thead>
    <tbody>
      <?php
      foreach ($pool['teams'] as $k => $team) { ?>
        <tr class="css-1408fvk">
          <td style="text-align: center;"><?php echo $team['position']; ?></td>
          <td style="text-align: center;">
            <div style="display: flex; align-items: center; gap: 15px;">
              <img width="32" height="32" src="<?php echo $team['crest']; ?>" alt="<?php echo $team['name'] ?>">
              <?php echo $team['name'] ?>
            </div>
          </td>
          <td style="text-align: center;"><?php echo $team['matchesPlayed']; ?></td>
          <td style="text-align: center;"><?php echo $team['matchesWon']; ?></td>
          <td style="text-align: center;"><?php echo $team['matchesDrawn']; ?></td>
          <td style="text-align: center;"><?php echo $team['matchesLost']; ?></td>
          <td style="text-align: center;"><?php echo $team['pointsDifference']; ?></td>
          <td style="text-align: center;"><?php echo $team['bonusPoints4T']; ?></td>
          <td style="text-align: center;"><?php echo $team['totalMatchPoints']; ?></td>
        </tr>
      <?php
      }
      ?>


    </tbody>
  </table>
<?php
}
?>