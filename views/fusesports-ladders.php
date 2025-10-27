<h2>Results<br><small><?php echo $data['full_name']; ?></small></h2>


<table class="ladders" border cellspacing="0">
  <thead>
    <tr>
      <th>Team</th>
      <th>P</th>
      <th>W</th>
      <th>D</th>
      <th>L</th>
      <th>F</th>
      <th>A</th>
      <th>Df</th>
      <th>BP</th>
      <th>Pts</th>
    </tr>
  </thead>
  <tbody>
    <?php
    // $data['ladder']
    foreach ($ladder_data as $ladder) { ?>
      <tr class="withIcon">
        <td data-team="519239"><?php echo $ladder['team_name']; ?></td>
        <td><?php echo $ladder['played']; ?></td>
        <td><?php echo $ladder['wins']; ?></td>
        <td><?php echo $ladder['draws']; ?></td>
        <td><?php echo $ladder['losses']; ?></td>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
        <td><?php echo $ladder['pointsdifference']; ?></td>
        <td><?php echo $ladder['bonuspoints']; ?></td>
        <td><?php echo $ladder['ladderpoints']; ?></td>
      </tr>
    <?php
    }
    ?>

  </tbody>
</table>

<?php
if (!empty($data['final_placings'])) { ?>
  <h3>Playoffs - Final results</h3>

  <table class="final-placings" border cellspacing="0">
    <thead>
      <tr>
        <th>Team</th>
        <th>Final Placing</th>
      </tr>
    </thead>
    <tbody>
      <?php
      foreach ($data['final_placings'] as $placing) {
      ?>
        <tr>
          <td><?php echo $placing['TeamName']; ?></td>
          <td><?php echo $placing['Placing']; ?></td>
        </tr>
      <?php } ?>
    </tbody>
  </table>
<?php
}
?>