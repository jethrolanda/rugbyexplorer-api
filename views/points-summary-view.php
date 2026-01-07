<?php
global $rea;
$tries = $data['allMatchStatsSummary']['pointsSummary']['tries'];
$conversions = $data['allMatchStatsSummary']['pointsSummary']['conversions'];
$scores = array(
  'tries' => 'Tries',
  'conversions' => 'Conversions',
  'penaltyGoals' => 'Penalty Goals',
  'fieldGoals' => 'Field Goals'
);
$fixture_item = $data['getFixtureItem'];
$is_home = false;

if (!empty($fixture_item)) {
  $is_home = in_array($fixture_item['homeTeam']['teamId'], $rea->rugbyexplorer->get_team_ids());
}
error_log(print_r($data['getFixtureItem'], true));
error_log(print_r($is_home, true));

error_log(print_r($rea->rugbyexplorer->get_team_ids(), true));


?>
<div class="container" style="display:flex; margin-bottom: 20px; justify-content: center;">
  <div style="display: flex; flex-direction: column; align-items: center;">
    <?php if ($is_home) { ?>
      <img width="100" height="100" src="<?php echo $data['getFixtureItem']['homeTeam']['crest']; ?>" alt="<?php echo $data['getFixtureItem']['homeTeam']['name']; ?>">
      <b><?php echo $data['getFixtureItem']['homeTeam']['name']; ?></b>
    <?php } else {  ?>
      <img width="100" height="100" src="<?php echo $data['getFixtureItem']['awayTeam']['crest']; ?>" alt="<?php echo $data['getFixtureItem']['awayTeam']['name']; ?>">
      <b><?php echo $data['getFixtureItem']['awayTeam']['name']; ?></b>
    <?php } ?>
  </div>
</div>
<?php
foreach ($data['allMatchStatsSummary']['pointsSummary'] as $key => $score) {
  if (isset($scores[$key]) && $score) { ?>
    <div style="display: flex; justify-content: center;">
      <div style="display: flex; flex-direction: column; align-items: center;">
        <b><?php echo $scores[$key]; ?></b>
        <?php
        foreach ($score as $sc) {
          if ($sc['isHome'] == $is_home) { ?>
            <div>
              <span><?php echo $sc['playerName']; ?></span> <?php echo $sc['pointsMinute']; ?>'
            </div>
        <?php
          }
        } ?>
      </div>
    </div>
<?php
  }
}
?>